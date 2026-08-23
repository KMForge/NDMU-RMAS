<?php

namespace App\Http\Controllers\Student;

use App\Enums\DocumentStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Models\Document;
use App\Modules\Documents\Actions\RecordDocumentUploadAttempt;
use App\Modules\Documents\Actions\SubmitDocument;
use App\Modules\Documents\Actions\SubmitTitleProposalForScreening;
use App\Modules\Documents\Exceptions\DocumentUploadFailed;
use App\Modules\Documents\Exceptions\DuplicateDocumentSubmission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Throwable;

class DocumentController extends Controller
{
    public function submitTitleProposal(
        Request $request,
        Document $document,
        SubmitTitleProposalForScreening $submit,
    ): JsonResponse|RedirectResponse {
        try {
            $submitted = $submit->handle($request->user(), $document);
        } catch (AuthorizationException $exception) {
            return $request->expectsJson()
                ? response()->json(['message' => $exception->getMessage()], 403)
                : back()->withErrors(['document' => $exception->getMessage()]);
        } catch (\InvalidArgumentException $exception) {
            return $request->expectsJson()
                ? response()->json(['message' => $exception->getMessage()], 422)
                : back()->withErrors(['document' => $exception->getMessage()]);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Title Proposal submitted for screening.', 'document' => $this->safeDocumentPayload($submitted)])
            : back()->with('document_success', 'Title Proposal submitted for facilitator screening.');
    }

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
                documentStage: DocumentStage::from($request->string('document_stage')->toString()),
            );
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 403);
        } catch (DuplicateDocumentSubmission $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 409);
        } catch (DocumentUploadFailed $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 500);
        } catch (Throwable $exception) {
            report($exception);

            $message = 'The document could not be uploaded. Please try again.';

            return $this->errorResponse($request, $message, 500);
        }

        $payload = $this->safeDocumentPayload($document);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $document->document_stage === DocumentStage::TitleProposal
                    ? 'Title Proposal uploaded as a draft. Submit it when ready for facilitator screening.'
                    : 'Document submitted successfully.',
                'document' => $payload,
            ], 201);
        }

        return to_route('student.dashboard', ['tab' => 'proposal'])
            ->with('document_success', $document->document_stage === DocumentStage::TitleProposal
                ? 'Title Proposal uploaded as a draft. Submit it when ready for facilitator screening.'
                : 'Document submitted successfully and is pending review.');
    }

    /**
     * @return array<string, mixed>
     */
    private function safeDocumentPayload(Document $document): array
    {
        return [
            'id' => $document->getKey(),
            'research_class_group_id' => $document->research_class_group_id,
            'original_filename' => $document->original_filename,
            'file_type' => $document->file_type,
            'document_stage' => $document->document_stage?->value,
            'file_size' => $document->file_size,
            'version_number' => $document->version_number,
            'is_current' => $document->is_current,
            'submitted_at' => $document->submitted_at?->toIso8601String(),
            'status' => $document->status->value,
            'view_url' => route('documents.view', $document),
            'download_url' => route('documents.download', $document),
        ];
    }

    private function errorResponse(
        StoreDocumentRequest $request,
        string $message,
        int $status,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('student.dashboard', ['tab' => 'proposal'])
            ->withErrors(['document' => $message])
            ->with('document_error', $message);
    }
}
