<?php

namespace App\Modules\Documents\Queries;

use App\Models\Defense;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GetPanelistAssignedDocuments
{
    /** @return Collection<int, array<string, mixed>> */
    public function for(User $panelist): Collection
    {
        if (! $panelist->can('documents.download')) {
            return collect();
        }

        return Document::query()
            ->where('is_current', true)
            ->whereNotNull('research_class_group_id')
            ->whereHas('researchClassGroup.defenses', fn (Builder $defense) => $defense
                ->whereColumn('defenses.defense_type', 'documents.document_stage')
                ->whereIn('defenses.status', ['scheduled', 'completed'])
                ->whereHas('activePanelAssignments', fn (Builder $assignment) => $assignment
                    ->where('user_id', $panelist->getKey())))
            ->with([
                'user:id,name,email',
                'comments' => fn ($comments) => $comments
                    ->with('author:id,name')
                    ->latest(),
                'researchClassGroup:id,research_class_id,research_group_id,name,adviser_id,status,disbanded_at',
                'researchClassGroup.adviser:id,name,email',
                'researchClassGroup.members.student:id,name,email',
                'researchClassGroup.researchGroup.currentProject',
                'researchClassGroup.defenses' => fn ($defenses) => $defenses
                    ->whereIn('status', ['scheduled', 'completed'])
                    ->whereHas('activePanelAssignments', fn (Builder $assignment) => $assignment
                        ->where('user_id', $panelist->getKey()))
                    ->with([
                        'activePanelAssignments' => fn ($assignments) => $assignments
                            ->where('user_id', $panelist->getKey()),
                        'evaluationRounds' => fn ($rounds) => $rounds
                            ->latest('opened_at')
                            ->with(['evaluations' => fn ($evaluations) => $evaluations
                                ->where('panelist_user_id', $panelist->getKey())]),
                    ]),
            ])
            ->latest('submitted_at')
            ->get()
            ->map(fn (Document $document): array => $this->format($document));
    }

    /** @return array<string, mixed> */
    private function format(Document $document): array
    {
        $group = $document->researchClassGroup;
        $stage = $document->document_stage?->value;
        /** @var Defense|null $defense */
        $defense = $group?->defenses->first(
            fn (Defense $candidate): bool => $candidate->defense_type === $stage,
        );
        $round = $defense?->evaluationRounds->first();
        $evaluation = $round?->evaluations->first();
        $status = match ($evaluation?->status) {
            'submitted' => 'Evaluated',
            'draft' => 'Under Review',
            default => $round === null ? 'Pending Defense' : 'For Review',
        };
        $defenseLabel = $document->document_stage?->label() ?? 'Research Defense';
        $researchTitle = $group?->researchGroup?->currentProject?->title
            ?? $group?->name
            ?? $document->original_filename;

        return [
            'id' => $document->getKey(),
            'defenseId' => $defense?->getKey(),
            'title' => $researchTitle,
            'filename' => $document->original_filename,
            'college' => 'College of Engineering, Architecture, and Computing (CEAC)',
            'researchers' => $group?->members->map(fn ($member): array => [
                'name' => $member->student?->name ?? 'Student Researcher',
                'bg' => 'bg-[#0e5c3a] text-white',
                'init' => Str::upper(Str::substr($member->student?->name ?? 'S', 0, 1)),
            ])->values()->all() ?? [],
            'adviser' => $group?->adviser?->name ?? 'Not assigned',
            'status' => $status,
            'statusClass' => $this->statusClass($status),
            'defenseType' => $defenseLabel,
            'defenseTypeClass' => $this->defenseTypeClass($stage),
            'submitted' => $document->submitted_at?->format('M d, Y') ?? '',
            'date' => $document->submitted_at?->format('M d, Y') ?? '',
            'author' => $document->user?->name ?? 'Student Researcher',
            'fileSize' => $document->formattedFileSize(),
            'fileType' => Str::upper($document->file_type),
            'chapter' => Str::upper($defenseLabel),
            'desc' => $document->original_filename,
            'topBorder' => $document->file_type === 'pdf'
                ? 'border-t-4 border-t-red-500'
                : 'border-t-4 border-t-blue-500',
            'viewUrl' => route('documents.view', $document),
            'downloadUrl' => route('documents.download', $document),
            'evaluationUrl' => route('panelist.dashboard', [
                'tab' => in_array($stage, ['title_proposal', 'proposal_defense'], true)
                    ? 'proposal-eval'
                    : 'final-eval',
            ]),
            'reviewUrl' => route('panelist.dashboard', [
                'tab' => 'recommendations',
                'document_id' => $document->getKey(),
            ]),
            'commentUrl' => route('panelist.documents.comments.store', $document),
            'comments' => $document->comments->map(fn ($comment): array => [
                'id' => $comment->getKey(),
                'name' => $comment->author?->name ?? 'Panel Member',
                'role' => 'Reviewer',
                'time' => $comment->created_at?->diffForHumans() ?? '',
                'text' => $comment->comment,
                'page' => $comment->page_number === null ? 'General' : 'Page '.$comment->page_number,
                'severity' => $comment->severity,
                'borderClass' => match ($comment->severity) {
                    'critical' => 'border-l-4 border-l-red-500 border border-red-100 bg-red-50/30',
                    'revision' => 'border-l-4 border-l-amber-500 border border-amber-100 bg-amber-50/30',
                    default => 'border-l-4 border-l-blue-500 border border-blue-100 bg-blue-50/30',
                },
            ])->values()->all(),
        ];
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'Evaluated' => 'bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            'Under Review' => 'bg-blue-50 border border-blue-200 text-blue-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            'Pending Defense' => 'bg-purple-50 border border-purple-200 text-purple-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            default => 'bg-amber-50 border border-amber-200 text-amber-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
        };
    }

    private function defenseTypeClass(?string $stage): string
    {
        return match ($stage) {
            'pre_final_defense' => 'bg-blue-50 border border-blue-200 text-blue-800 font-bold px-2 py-0.5 rounded-full text-[10px]',
            'final_defense' => 'bg-purple-50 border border-purple-200 text-purple-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            default => 'bg-yellow-50 border border-yellow-200 text-yellow-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
        };
    }
}
