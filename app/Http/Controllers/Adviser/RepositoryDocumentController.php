<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Modules\Documents\Actions\RecordDocumentUploadAttempt;
use App\Modules\Documents\Actions\SubmitDocument;
use App\Modules\Documents\Exceptions\DocumentUploadFailed;
use App\Modules\Documents\Exceptions\DuplicateDocumentSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Throwable;

class RepositoryDocumentController extends Controller
{
    public function store(
        StoreDocumentRequest $request,
        SubmitDocument $submitDocument,
        RecordDocumentUploadAttempt $audit,
    ): JsonResponse|RedirectResponse {
        /** @var UploadedFile $file */
        $file = $request->file('document');

        try {
            $document = $submitDocument->handle(
                $request->user(),
                $file,
                $request->string('submission_token')->toString(),
                $request->ip(),
            );
        } catch (DuplicateDocumentSubmission $exception) {
            $audit->failure($request->user(), $file, $request->ip(), $exception->getMessage());

            return $this->errorResponse($request, $exception->getMessage(), 409);
        } catch (DocumentUploadFailed $exception) {
            $audit->failure($request->user(), $file, $request->ip(), $exception->getMessage());

            return $this->errorResponse($request, $exception->getMessage(), 500);
        } catch (Throwable $exception) {
            report($exception);

            $message = 'The document could not be uploaded. Please try again.';
            $audit->failure($request->user(), $file, $request->ip(), $message);

            return $this->errorResponse($request, $message, 500);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document uploaded to the repository successfully.',
                'document' => [
                    'id' => $document->getKey(),
                    'original_filename' => $document->original_filename,
                    'file_type' => $document->file_type,
                    'file_size' => $document->file_size,
                    'submitted_at' => $document->submitted_at?->toIso8601String(),
                    'status' => $document->status->value,
                    'view_url' => route('documents.view', $document),
                    'download_url' => route('documents.download', $document),
                ],
            ], 201);
        }

        return to_route('adviser.dashboard', ['tab' => 'repository'])
            ->with('document_success', 'Document uploaded to the repository successfully.');
    }

    private function errorResponse(
        StoreDocumentRequest $request,
        string $message,
        int $status,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('adviser.dashboard', ['tab' => 'repository'])
            ->withErrors(['document' => $message])
            ->with('document_error', $message);
    }
}
