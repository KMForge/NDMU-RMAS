<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\CorrectDocumentReviewDecisionRequest;
use App\Http\Requests\Documents\ReviewDocumentRequest;
use App\Http\Requests\Documents\StoreDocumentReviewCommentRequest;
use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Modules\Documents\Actions\AddDocumentReviewComment;
use App\Modules\Documents\Actions\CorrectDocumentReviewDecision;
use App\Modules\Documents\Actions\ResolveDocumentReviewComment;
use App\Modules\Documents\Actions\ReviewDocument;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentReviewController extends Controller
{
    public function comment(
        StoreDocumentReviewCommentRequest $request,
        Document $document,
        AddDocumentReviewComment $addComment,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('review', $document);

        try {
            $comment = $addComment->handle(
                $request->user(),
                $document,
                $request->validated(),
            );
        } catch (DocumentReviewException $exception) {
            return $this->errorResponse($request, $document, $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Comment posted successfully.',
                'comment' => [
                    'id' => $comment->getKey(),
                    'comment' => $comment->comment,
                    'severity' => $comment->severity,
                    'page_number' => $comment->page_number,
                    'author_name' => $comment->author->name,
                    'created_at' => $comment->created_at->toIso8601String(),
                ],
            ], 201);
        }

        return $this->redirectToDocument($document)
            ->with('document_review_success', 'Comment posted successfully.');
    }

    public function resolve(
        Request $request,
        Document $document,
        DocumentReviewComment $comment,
        ResolveDocumentReviewComment $resolveComment,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('review', $document);

        try {
            $resolvedComment = $resolveComment->handle(
                $request->user(),
                $document,
                $comment,
            );
        } catch (DocumentReviewException $exception) {
            return $this->errorResponse($request, $document, $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Comment resolved successfully.',
                'comment' => [
                    'id' => $resolvedComment->getKey(),
                    'resolved_at' => $resolvedComment->resolved_at?->toIso8601String(),
                ],
            ]);
        }

        return $this->redirectToDocument($document)
            ->with('document_review_success', 'Comment resolved successfully.');
    }

    public function review(
        ReviewDocumentRequest $request,
        Document $document,
        ReviewDocument $reviewDocument,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('review', $document);

        try {
            $review = $reviewDocument->handle(
                $request->user(),
                $document,
                $request->string('decision')->toString(),
                $request->validated('review_notes'),
                $request->ip(),
            );
        } catch (DocumentReviewException $exception) {
            return $this->errorResponse($request, $document, $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document review decision saved successfully.',
                'review' => [
                    'id' => $review->getKey(),
                    'document_id' => $document->getKey(),
                    'decision' => $review->decision,
                    'reviewed_at' => $review->reviewed_at->toIso8601String(),
                ],
            ]);
        }

        return $this->redirectToDocument($document)
            ->with('document_review_success', 'Document review decision saved successfully.');
    }

    public function correct(
        CorrectDocumentReviewDecisionRequest $request,
        Document $document,
        CorrectDocumentReviewDecision $correctDecision,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('review', $document);

        try {
            $review = $correctDecision->handle(
                $request->user(),
                $document,
                $request->string('decision')->toString(),
                $request->string('correction_reason')->toString(),
                $request->validated('review_notes'),
                $request->ip(),
            );
        } catch (DocumentReviewException $exception) {
            return $this->errorResponse($request, $document, $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document review decision corrected successfully.',
                'review' => [
                    'id' => $review->getKey(),
                    'document_id' => $document->getKey(),
                    'decision' => $review->decision,
                    'correction_reason' => $review->correction_reason,
                    'reviewed_at' => $review->reviewed_at->toIso8601String(),
                ],
            ]);
        }

        return $this->redirectToDocument($document)
            ->with('document_review_success', 'Document review decision corrected successfully.');
    }

    private function errorResponse(
        Request $request,
        Document $document,
        string $message,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 409);
        }

        return $this->redirectToDocument($document)
            ->withErrors(['document_review' => $message]);
    }

    private function redirectToDocument(Document $document): RedirectResponse
    {
        return to_route('adviser.dashboard', [
            'tab' => 'docreview',
            'document_id' => $document->getKey(),
            'document_status' => 'all',
        ]);
    }
}
