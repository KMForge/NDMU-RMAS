<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Filesystem\FilesystemAdapter;
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
            $disk = $this->documentDisk($document);

            if (! $disk->exists($document->storage_path)) {
                return $this->errorResponse($request, 'Document not found.', 404);
            }

            return $disk->response(
                $document->storage_path,
                $document->original_filename,
                $this->previewHeaders($document),
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
            $disk = $this->documentDisk($document);

            if (! $disk->exists($document->storage_path)) {
                return $this->errorResponse($request, 'Document not found.', 404);
            }

            return $disk->download(
                $document->storage_path,
                $document->original_filename,
                $this->downloadHeaders($document),
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse($request, 'The document is temporarily unavailable.', 500);
        }
    }

    private function documentDisk(Document $document): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($document->storage_disk);

        return $disk;
    }

    /**
     * @return array<string, string>
     */
    private function previewHeaders(Document $document): array
    {
        return [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Cache-Control' => 'private, no-store, max-age=0',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function downloadHeaders(Document $document): array
    {
        return [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cross-Origin-Resource-Policy' => 'same-origin',
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
