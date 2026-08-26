<?php

namespace App\Modules\ReportsAnalytics\Exports;

use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvReportExporter
{
    /** @param list<string> $columns @param list<array<string,mixed>> $rows */
    public function download(string $report, array $columns, array $rows): StreamedResponse
    {
        $this->guardLimit($rows);
        $filename = $this->filename($report, 'csv');

        return response()->streamDownload(function () use ($columns, $rows): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $columns);
            foreach ($rows as $row) {
                fputcsv($output, array_map(fn ($column) => $this->safeCell($row[$column] ?? ''), $columns));
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function safeCell(mixed $value): string
    {
        $text = is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR);
        if (preg_match('/^[\x00-\x20]*[=+\-@]/u', $text) === 1 || preg_match('/^[\t\r\n]/', $text) === 1) {
            return "'".$text;
        }

        return $text;
    }

    /** @param list<array<string,mixed>> $rows */
    public function guardLimit(array $rows): void
    {
        $this->guardCount(count($rows));
    }

    public function guardCount(int $count): void
    {
        abort_if($count > (int) config('analytics.max_export_rows', 10000), 422, 'This report exceeds the configured export row limit. Narrow the filters and try again.');
    }

    private function filename(string $report, string $extension): string
    {
        return 'ndmu-rmas-'.str($report)->slug().'-'.now()->format('Ymd-His').'.'.$extension;
    }
}
