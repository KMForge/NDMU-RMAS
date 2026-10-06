<?php

namespace App\Modules\Documents\Jobs;

use App\Models\Document;
use App\Modules\Documents\Services\ExtractPaperMetadata;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExtractDocumentMetadata implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 45;

    public function __construct(public readonly int $documentId) {}

    public function handle(ExtractPaperMetadata $extractor): void
    {
        $document = Document::query()->find($this->documentId);

        // A delayed job must not process a deleted or superseded upload.
        if ($document === null || ! $document->is_current) {
            return;
        }

        $extractor->extractAndSync($document);
    }
}
