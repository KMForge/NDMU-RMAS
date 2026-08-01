<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OfficialFormController extends Controller
{
    public function source(string $form): BinaryFileResponse
    {
        $definition = config("official-forms.student.{$form}");

        abort_unless(is_array($definition) && isset($definition['file']), 404);

        $path = resource_path('forms/student/'.$definition['file']);

        abort_unless(is_string($path) && is_file($path), 404, 'The official forms document is unavailable.');

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.strtolower($form).'.pdf"',
            'Cache-Control' => 'private, max-age=3600',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
