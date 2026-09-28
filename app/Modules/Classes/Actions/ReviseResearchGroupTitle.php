<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchProject;
use App\Models\ResearchProjectTitleHistory;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Classes\Exceptions\ClassOperationException;
use Illuminate\Support\Facades\DB;

class ReviseResearchGroupTitle
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        string $revisedTitle,
        string $reason,
    ): ResearchProject {
        return DB::transaction(function () use ($facilitator, $researchClass, $group, $revisedTitle, $reason): ResearchProject {
            $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());
            if ((int) $lockedClass->facilitator_id !== (int) $facilitator->getKey()) {
                throw new ClassOperationException('Only the owning Research Facilitator may revise this title.');
            }

            $lockedGroup = ResearchClassGroup::query()
                ->whereKey($group->getKey())
                ->where('research_class_id', $lockedClass->getKey())
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($lockedGroup === null) {
                throw new ClassOperationException('The active research group was not found in this class.');
            }
            if ($lockedGroup->research_group_id === null) {
                throw new ClassOperationException('The title can be revised after RES-026 has finalized the canonical research project.');
            }

            $project = ResearchProject::query()
                ->where('research_group_id', $lockedGroup->research_group_id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($project === null) {
                throw new ClassOperationException('The canonical research project could not be found.');
            }

            $newTitle = trim($revisedTitle);
            $oldTitle = trim($project->title);
            if (mb_strtolower($newTitle) === mb_strtolower($oldTitle)) {
                throw new ClassOperationException('Enter a revised title that is different from the current title.');
            }

            $effectiveAt = now();
            ResearchProjectTitleHistory::query()->create([
                'research_project_id' => $project->getKey(),
                'previous_title' => $oldTitle,
                'revised_title' => $newTitle,
                'reason' => trim($reason),
                'changed_by' => $facilitator->getKey(),
                'effective_at' => $effectiveAt,
            ]);
            $project->update(['title' => $newTitle]);

            $this->auditLogs->write(
                actor: $facilitator,
                event: 'research-project.title-revised',
                description: 'The Research Facilitator revised the canonical research title.',
                requestContext: AuditRequestContext::fromRequest(request()),
                auditable: $project,
                subjectName: $newTitle,
                oldValues: ['title' => $oldTitle],
                newValues: ['title' => $newTitle, 'reason' => trim($reason), 'effective_at' => $effectiveAt->toIso8601String()],
                actorContext: 'research-facilitator',
            );

            return $project->refresh();
        }, 3);
    }
}
