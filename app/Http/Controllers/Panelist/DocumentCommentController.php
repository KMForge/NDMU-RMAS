<?php

namespace App\Http\Controllers\Panelist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreDocumentReviewCommentRequest;
use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\User;
use App\Modules\Documents\Actions\AddDocumentReviewComment;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
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

        $res039Url = $this->resolveRes039Url($request->user(), $document, $comment);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Comment posted successfully and logged to Research Revision Chart (RES-039).',
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
                'revision_chart_url' => $res039Url,
            ]);
        }

        return to_route('panelist.dashboard', [
            'tab' => 'recommendations',
            'document_id' => $document->getKey(),
        ])->with('success', 'Comment posted and recorded in Research Revision Chart (RES-039). You can add another critique or finish your review.');
    }

    private function resolveRes039Url(User $user, Document $document, DocumentReviewComment $comment): string
    {
        $group = $document->researchClassGroup;
        if (! $group) {
            return route('panelist.dashboard', [
                'tab' => 'recommendations',
                'document_id' => $document->getKey(),
            ]);
        }

        $def = OfficialFormDefinition::query()->where('code', 'RES-039')->first();
        if (! $def) {
            return route('panelist.dashboard', [
                'tab' => 'recommendations',
                'document_id' => $document->getKey(),
            ]);
        }

        $instance = OfficialFormInstance::query()
            ->where('official_form_definition_id', $def->id)
            ->where('research_class_group_id', $group->id)
            ->latest('id')
            ->first();

        if (! $instance) {
            try {
                $instance = app(CreateOfficialFormInstance::class)->handle(
                    initiator: $user,
                    formCode: 'RES-039',
                    groupId: $group->id,
                    classId: null,
                    contextKey: 'document-review-'.$document->id,
                    sourceType: Document::class,
                    sourceId: $document->id,
                    payload: [
                        'research_title' => $group->title ?? $group->name,
                    ],
                );
            } catch (\Throwable) {
                $instance = OfficialFormInstance::query()
                    ->where('official_form_definition_id', $def->id)
                    ->where('research_class_group_id', $group->id)
                    ->latest('id')
                    ->first();
            }
        }

        if ($instance && $instance->currentVersion) {
            $version = $instance->currentVersion;
            $payload = $version->payload ?? [];
            $revisions = $payload['revisions'] ?? [];
            $others = $revisions['others'] ?? [];

            $commentText = $comment->comment;
            $alreadyExists = collect($others)->contains(fn ($row) => ($row['suggestions'] ?? '') === $commentText);

            if (! $alreadyExists) {
                $others[] = [
                    'suggestions' => $commentText,
                    'recommended_by' => $user->name,
                    'revision_made' => '',
                    'pages' => $comment->page_number ? 'Page '.$comment->page_number : '',
                    'approval' => '',
                ];
                $revisions['others'] = $others;
                $payload['revisions'] = $revisions;
                $version->update(['payload' => $payload]);
            }
        }

        return $instance
            ? route('official-forms.workspace.show', $instance)
            : route('panelist.dashboard', ['tab' => 'recommendations', 'document_id' => $document->getKey()]);
    }
}
