<?php

namespace App\Modules\Documents\Services;

use App\Models\Document;
use App\Models\ResearchProject;
use DOMDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class ExtractPaperMetadata
{
    /**
     * @return array{abstract: ?string, keywords: array<int, string>}
     */
    public function extract(Document $document): array
    {
        $diskName = $document->storage_disk ?: (string) config('ndmu-rmas.document.storage_disk', 'local');
        $disk = Storage::disk($diskName);

        if (! $disk->exists($document->storage_path)) {
            return ['abstract' => null, 'keywords' => []];
        }

        $extension = strtolower((string) ($document->file_type ?: pathinfo($document->original_filename, PATHINFO_EXTENSION)));

        $tempFile = null;
        try {
            if (method_exists($disk, 'path')) {
                $filePath = $disk->path($document->storage_path);
            } else {
                $tempFile = tempnam(sys_get_temp_dir(), 'rmas_extract_');
                if ($tempFile === false) {
                    return ['abstract' => null, 'keywords' => []];
                }
                file_put_contents($tempFile, $disk->get($document->storage_path));
                $filePath = $tempFile;
            }

            if (! file_exists($filePath) || filesize($filePath) === 0) {
                return ['abstract' => null, 'keywords' => []];
            }

            return match ($extension) {
                'docx' => $this->extractFromDocx($filePath),
                'pdf' => $this->extractFromPdf($filePath),
                default => ['abstract' => null, 'keywords' => []],
            };
        } catch (Throwable $e) {
            report($e);

            return ['abstract' => null, 'keywords' => []];
        } finally {
            if ($tempFile !== null && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Extract metadata from document and synchronize to its associated ResearchProject.
     */
    public function extractAndSync(Document $document): ?ResearchProject
    {
        $extracted = $this->extract($document);

        if (empty($extracted['abstract']) && empty($extracted['keywords'])) {
            return null;
        }

        $group = $document->researchClassGroup;
        $researchGroupId = $group?->research_group_id;

        $project = null;
        if ($researchGroupId) {
            $project = ResearchProject::query()
                ->where('research_group_id', $researchGroupId)
                ->whereNull('archived_at')
                ->latest('updated_at')
                ->first();
        }

        if ($project === null && $document->user_id) {
            $project = ResearchProject::query()
                ->where('created_by', $document->user_id)
                ->whereNull('archived_at')
                ->latest('updated_at')
                ->first();
        }

        if ($project !== null) {
            $updates = [];

            if (! empty($extracted['abstract'])) {
                $project->abstract = $extracted['abstract'];
                $updates['abstract'] = $extracted['abstract'];
            }

            if (! empty($extracted['keywords'])) {
                $project->keywords = $extracted['keywords'];
                $updates['keywords'] = json_encode($extracted['keywords']);
            }

            if (! empty($updates)) {
                $updates['updated_at'] = now();
                DB::table('research_projects')
                    ->where('id', $project->id)
                    ->update($updates);

                $project->refresh();
            }
        }

        return $project;
    }

    /**
     * @return array{abstract: ?string, keywords: array<int, string>}
     */
    private function extractFromDocx(string $fullPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($fullPath) !== true) {
            return ['abstract' => null, 'keywords' => []];
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($xml) || $xml === '') {
            return ['abstract' => null, 'keywords' => []];
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_clear_errors();

        $paragraphs = [];
        foreach ($dom->getElementsByTagName('p') as $p) {
            $text = '';
            foreach ($p->getElementsByTagName('t') as $t) {
                $text .= $t->nodeValue;
            }
            $clean = trim((string) preg_replace('/\s+/u', ' ', $text));
            if ($clean !== '') {
                $paragraphs[] = $clean;
            }
        }

        return $this->parseParagraphs($paragraphs);
    }

    /**
     * @return array{abstract: ?string, keywords: array<int, string>}
     */
    private function extractFromPdf(string $fullPath): array
    {
        $text = '';

        // Strategy 1: Attempt pdftotext if available on the system
        try {
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            $redirect = $isWindows ? ' 2>nul' : ' 2>/dev/null';
            $output = [];
            $returnVar = 0;
            @exec('pdftotext -f 1 -l 5 '.escapeshellarg($fullPath).' -'.$redirect, $output, $returnVar);
            if ($returnVar === 0 && ! empty($output)) {
                $text = implode("\n", $output);
            }
        } catch (Throwable) {
            // Fallback to internal parser
        }

        // Strategy 2: Stream extraction fallback
        if (trim($text) === '') {
            $content = @file_get_contents($fullPath);
            if (is_string($content) && $content !== '') {
                $text = $this->extractTextFromPdfStream($content);
            }
        }

        if (trim($text) === '') {
            return ['abstract' => null, 'keywords' => []];
        }

        $rawLines = preg_split('/\r\n|\r|\n/u', $text) ?: [];
        $paragraphs = [];
        $current = '';

        foreach ($rawLines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($current !== '') {
                    $paragraphs[] = $current;
                    $current = '';
                }
            } else {
                $current = $current === '' ? $trimmed : $current.' '.$trimmed;
            }
        }

        if ($current !== '') {
            $paragraphs[] = $current;
        }

        return $this->parseParagraphs($paragraphs);
    }

    /**
     * @param  array<int, string>  $paragraphs
     * @return array{abstract: ?string, keywords: array<int, string>}
     */
    private function parseParagraphs(array $paragraphs): array
    {
        $abstractParagraphs = [];
        $keywords = [];
        $inAbstract = false;

        $stopHeadings = [
            'acknowledgment',
            'acknowledgements',
            'acknowledgement',
            'table of contents',
            'contents',
            'list of tables',
            'list of figures',
            'chapter 1',
            'chapter i',
            '1. introduction',
            'introduction',
            'background of the study',
            'executive summary',
        ];

        foreach ($paragraphs as $idx => $text) {
            $clean = trim($text);
            $lower = strtolower($clean);

            // Abstract section header
            if (! $inAbstract && preg_match('/^(?:abstract|executive\s+summary)$/i', $clean)) {
                $inAbstract = true;

                continue;
            }

            if ($inAbstract) {
                // Check if this line is the Keywords line
                if (preg_match('/^(?:keywords?|key\s+words|index\s+terms)\s*[:\-–—]\s*(.+)$/i', $clean, $matches)) {
                    $rawKeywords = $matches[1];
                    $split = preg_split('/[,;•|]/u', $rawKeywords) ?: [];
                    foreach ($split as $kw) {
                        $trimmed = trim(trim($kw), ".\t\n\r\0\x0B");
                        if ($trimmed !== '') {
                            $keywords[] = $trimmed;
                        }
                    }
                    $inAbstract = false;

                    continue;
                }

                // Check for subsequent section stop headings
                $isStop = false;
                foreach ($stopHeadings as $stop) {
                    if ($lower === $stop || str_starts_with($lower, $stop.' ') || str_starts_with($lower, $stop."\t")) {
                        $isStop = true;
                        break;
                    }
                }

                if ($isStop) {
                    $inAbstract = false;

                    continue;
                }

                // Exclude standalone page numbers or index numbers
                if (! preg_match('/^\d+$/', $clean) && mb_strlen($clean) > 3) {
                    $abstractParagraphs[] = $clean;
                }
            } else {
                // Look for keywords in the first 80 paragraphs if not yet found
                if (empty($keywords) && $idx < 80 && preg_match('/^(?:keywords?|key\s+words|index\s+terms)\s*[:\-–—]\s*(.+)$/i', $clean, $matches)) {
                    $rawKeywords = $matches[1];
                    $split = preg_split('/[,;•|]/u', $rawKeywords) ?: [];
                    foreach ($split as $kw) {
                        $trimmed = trim(trim($kw), ".\t\n\r\0\x0B");
                        if ($trimmed !== '') {
                            $keywords[] = $trimmed;
                        }
                    }
                }
            }
        }

        $abstract = ! empty($abstractParagraphs) ? implode("\n\n", $abstractParagraphs) : null;

        return [
            'abstract' => $abstract,
            'keywords' => array_values(array_unique($keywords)),
        ];
    }

    private function extractTextFromPdfStream(string $content): string
    {
        $text = '';
        // Match uncompressed or flate-decoded streams
        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $streams);

        foreach ($streams[1] as $stream) {
            $data = $stream;
            $uncompressed = @gzuncompress($data);
            if ($uncompressed !== false) {
                $data = $uncompressed;
            }

            // Extract strings in parentheses (...) inside BT ... ET text blocks
            if (preg_match_all('/BT[\s\S]*?ET/s', $data, $textBlocks)) {
                foreach ($textBlocks[0] as $block) {
                    if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $block, $matches)) {
                        $line = implode('', $matches[1]);
                        $text .= $line."\n";
                    } elseif (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $arrayMatches)) {
                        foreach ($arrayMatches[1] as $arrayContent) {
                            if (preg_match_all('/\((.*?)\)/s', $arrayContent, $subMatches)) {
                                $text .= implode('', $subMatches[1]).' ';
                            }
                        }
                        $text .= "\n";
                    }
                }
            }
        }

        return $text;
    }
}
