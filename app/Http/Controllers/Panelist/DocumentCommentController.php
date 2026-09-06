<?php

namespace App\Http\Controllers\Panelist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreDocumentReviewCommentRequest;
use App\Models\Document;
use App\Modules\Documents\Actions\AddDocumentReviewComment;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DocumentCommentController extends Controller
{
    public function store(
        StoreDocumentReviewCommentRequest $request,
        Document $document,
        AddDocumentReviewComment $addComment,
    ): JsonResponse|RedirectResponse {
        try {
            $comment = $addComment->handle($request->user(), $document, $request->validated());
        } catch (DocumentReviewException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return to_route('panelist.dashboard', [
                'tab' => 'recommendations',
                'document_id' => $document->getKey(),
            ])->withErrors(['document_review' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Comment posted successfully.',
                'comment' => [
                    'id' => $comment->id,
                    'name' => $comment->author?->name ?? $request->user()->name,
                    'role' => 'Defense Panelist',
                    'time' => 'Just now',
                    'text' => $comment->comment,
                    'page' => $comment->page_number ? 'Page '.$comment->page_number : 'General Reference',
                    'page_number' => $comment->page_number,
                    'severity' => $comment->severity,
                    'borderClass' => match ($comment->severity) {
                        'critical' => 'border-l-4 border-l-red-500 border-slate-200 bg-red-50/20',
                        'revision' => 'border-l-4 border-l-amber-500 border-slate-200 bg-amber-50/20',
                        default => 'border-l-4 border-l-blue-500 border-slate-200 bg-blue-50/20',
                    },
                ],
            ]);
        }

        return to_route('panelist.dashboard', [
            'tab' => 'recommendations',
            'document_id' => $document->getKey(),
        ])->with('document_review_success', 'Comment posted successfully.');
    }
}
