<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DocumentAccessController extends Controller
{
    public function view(Request $request, Document $document): Response
    {
        if (Gate::denies('view', $document)) {
            return $this->errorResponse($request, 'You are not allowed to view this document.', 403);
        }

        try {
            if (! $this->fileExists($document)) {
                return $this->errorResponse($request, 'Document not found.', 404);
            }

            return Storage::disk($document->storage_disk)->response(
                $document->storage_path,
                $document->original_filename,
                $this->securityHeaders($document),
                'inline',
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse($request, 'The document is temporarily unavailable.', 500);
        }
    }

    public function download(Request $request, Document $document): Response
    {
        if (Gate::denies('download', $document)) {
            return $this->errorResponse($request, 'You are not allowed to download this document.', 403);
        }

        try {
            if (! $this->fileExists($document)) {
                return $this->errorResponse($request, 'Document not found.', 404);
            }

            return Storage::disk($document->storage_disk)->download(
                $document->storage_path,
                $document->original_filename,
                $this->securityHeaders($document),
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse($request, 'The document is temporarily unavailable.', 500);
        }
    }

    private function fileExists(Document $document): bool
    {
        return Storage::disk($document->storage_disk)->exists($document->storage_path);
    }

    /**
     * @return array<string, string>
     */
    private function securityHeaders(Document $document): array
    {
        return [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, no-store, max-age=0',
        ];
    }

    private function errorResponse(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return response($message, $status)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }
}
