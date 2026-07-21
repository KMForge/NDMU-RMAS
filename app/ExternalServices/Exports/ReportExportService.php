<?php

namespace App\ExternalServices\Exports;

interface ReportExportService
{
    /** @param iterable<array<string, mixed>> $rows */
    public function export(iterable $rows, string $format): string;
}
