<?php

namespace App\ExternalServices\Documents;

use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF as DomPdf;

class PdfService
{
    /** @param array<string, mixed> $data */
    public function fromView(string $view, array $data = []): DomPdf
    {
        return PdfFacade::loadView($view, $data);
    }
}
