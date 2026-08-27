<?php

namespace App\Http\Controllers\Panelist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreDocumentReviewCommentRequest;
use App\Models\Document;
use App\Modules\Documents\Actions\AddDocumentReviewComment;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use Illuminate\Http\RedirectResponse;

class DocumentCommentController extends Controller
{
    public function store(
        StoreDocumentReviewCommentRequest $request,
        Document $document,
        AddDocumentReviewComment $addComment,
    ): RedirectResponse {
        try {
            $addComment->handle($request->user(), $document, $request->validated());
        } catch (DocumentReviewException $exception) {
            return to_route('panelist.dashboard', [
                'tab' => 'recommendations',
                'document_id' => $document->getKey(),
            ])->withErrors(['document_review' => $exception->getMessage()]);
        }

        return to_route('panelist.dashboard', [
            'tab' => 'recommendations',
            'document_id' => $document->getKey(),
        ])->with('document_review_success', 'Comment posted successfully.');
    }
}
