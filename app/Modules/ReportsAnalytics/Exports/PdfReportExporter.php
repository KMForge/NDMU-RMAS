<?php

namespace App\Modules\ReportsAnalytics\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class PdfReportExporter
{
    /** @param array<string,mixed> $definition @param array<string,mixed> $result */
    public function download(string $report, array $definition, array $result, array $context): Response
    {
        $this->guardCount(count($result['rows']));

        return Pdf::loadView('pages.reports.pdf', compact('definition', 'result', 'context'))
            ->setPaper('a4', 'landscape')
            ->download('ndmu-rmas-'.str($report)->slug().'-'.now()->format('Ymd-His').'.pdf');
    }

    public function guardCount(int $count): void
    {
        abort_if($count > (int) config('analytics.max_export_rows', 10000), 422, 'This report exceeds the configured export row limit. Narrow the filters and try again.');
    }
}
