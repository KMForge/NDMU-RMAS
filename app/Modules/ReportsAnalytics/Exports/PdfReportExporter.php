<?php

namespace App\Modules\ReportsAnalytics\Exports;

use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Response;

final class PdfReportExporter
{
    /** @param array<string,mixed> $definition @param array<string,mixed> $result */
    public function download(string $report, array $definition, array $result, array $context): Response
    {
        $this->guardCount(count($result['rows']));

        // Resolve a fresh wrapper for each export. A cached Facade root reuses the
        // Dompdf canvas and font state across requests in long-running processes
        // and the report-catalog regression test, eventually exhausting memory.
        /** @var PDF $pdf */
        $pdf = app('dompdf.wrapper');

        $html = view('pages.reports.pdf', compact('definition', 'result', 'context'))->render();

        // The bundled DejaVu font is unnecessarily expensive for this ASCII
        // report template. Core Helvetica avoids repeated font embedding and
        // keeps catalog-wide exports within PHP's default memory limit.
        $html = str_replace('DejaVu Sans', 'Helvetica', $html);

        return $pdf->loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->download('ndmu-rmas-'.str($report)->slug().'-'.now()->format('Ymd-His').'.pdf');
    }

    public function guardCount(int $count): void
    {
        abort_if($count > (int) config('analytics.max_export_rows', 10000), 422, 'This report exceeds the configured export row limit. Narrow the filters and try again.');
    }
}
