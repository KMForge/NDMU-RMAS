<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Revisions\StoreRevisionDocumentRequest;
use App\Models\Document;
use App\Models\RevisionRequest;
use App\Modules\Documents\Actions\RecordDocumentUploadAttempt;
use App\Modules\Documents\Actions\SubmitDocument;
use App\Modules\Documents\Exceptions\DocumentUploadFailed;
use App\Modules\Documents\Exceptions\DuplicateDocumentSubmission;
use App\Modules\Revisions\Actions\TransitionRevisionRequest;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Throwable;

class RevisionRequestController extends Controller
{
    public function start(
        Request $request,
        RevisionRequest $revisionRequest,
        TransitionRevisionRequest $transition,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('start', $revisionRequest);

        try {
            $revisionRequest = $transition->start(
                $request->user(),
                $revisionRequest,
                $request->ip(),
            );
        } catch (RevisionWorkflowException $exception) {
            return $this->errorResponse($request, $exception->getMessage());
        }

        return $this->successResponse(
            $request,
            $revisionRequest,
            'Revision work started.',
        );
    }

    public function submit(
        StoreRevisionDocumentRequest $request,
        RevisionRequest $revisionRequest,
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
                $revisionRequest,
            );
        } catch (DuplicateDocumentSubmission|RevisionWorkflowException $exception) {
            $audit->failure($request->user(), $file, $request->ip(), $exception->getMessage());

            return $this->errorResponse($request, $exception->getMessage());
        } catch (DocumentUploadFailed $exception) {
            $audit->failure($request->user(), $file, $request->ip(), $exception->getMessage());

            return $this->errorResponse($request, $exception->getMessage(), 500);
        } catch (Throwable $exception) {
            report($exception);

            $message = 'The revised document could not be uploaded. Please try again.';
            $audit->failure($request->user(), $file, $request->ip(), $message);

            return $this->errorResponse($request, $message, 500);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Revised document submitted successfully.',
                'revision' => [
                    'id' => $revisionRequest->getKey(),
                    'status' => 'submitted',
                    'document' => $this->safeDocumentPayload($document),
                ],
            ], 201);
        }

        return to_route('student.dashboard', ['tab' => 'revisions'])
            ->with('revision_success', 'Revised document submitted successfully.');
    }

    private function successResponse(
        Request $request,
        RevisionRequest $revisionRequest,
        string $message,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'revision' => [
                    'id' => $revisionRequest->getKey(),
                    'status' => $revisionRequest->status->value,
                    'resolved_at' => $revisionRequest->resolved_at?->toIso8601String(),
                ],
            ]);
        }

        return to_route('student.dashboard', ['tab' => 'revisions'])
            ->with('revision_success', $message);
    }

    private function errorResponse(
        Request $request,
        string $message,
        int $status = 409,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('student.dashboard', ['tab' => 'revisions'])
            ->withErrors(['revision' => $message]);
    }

    /**
     * @return array<string, mixed>
     */
    private function safeDocumentPayload(Document $document): array
    {
        return [
            'id' => $document->getKey(),
            'original_filename' => $document->original_filename,
            'file_type' => $document->file_type,
            'file_size' => $document->file_size,
            'submitted_at' => $document->submitted_at?->toIso8601String(),
            'status' => $document->status->value,
            'view_url' => route('documents.view', $document),
            'download_url' => route('documents.download', $document),
        ];
    }
}
