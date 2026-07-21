<?php

namespace App\ExternalServices\Documents;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF;

class PdfService
{
    /** @param array<string, mixed> $data */
    public function fromView(string $view, array $data = []): PDF
    {
        return PDF::loadView($view, $data);
    }
}
