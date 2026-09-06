<?php

namespace App\Modules\OfficialForms\Services;

use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassActorAssignment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotifyNextRequiredOfficialForms
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications = new WorkflowNotificationDispatcher,
    ) {}

    public function handle(User $actor, OfficialFormInstance $instance): void
    {
        $instance->loadMissing([
            'definition',
            'group.researchClass.facilitator',
            'researchClass.facilitator',
        ]);

        $completedCode = strtolower((string) $instance->definition?->code);
        $completedSpecification = OfficialResearchWorkflowRegistry::getFormDefinition($completedCode);

        if ($completedSpecification === null
            || $instance->status !== $this->terminalStatus($completedSpecification)) {
            return;
        }

        foreach (OfficialResearchWorkflowRegistry::FORMS as $nextCode => $nextSpecification) {
            if (! in_array($completedCode, $nextSpecification['prerequisites'], true)
                || ! $this->prerequisitesAreComplete($instance, $nextSpecification['prerequisites'])) {
                continue;
            }

            $this->notifyRecipients($actor, $instance, $nextCode, $nextSpecification);
        }
    }

    /**
     * @param  list<string>  $prerequisites
     */
    private function prerequisitesAreComplete(OfficialFormInstance $source, array $prerequisites): bool
    {
        foreach ($prerequisites as $prerequisiteCode) {
            $specification = OfficialResearchWorkflowRegistry::getFormDefinition($prerequisiteCode);

            if ($specification === null) {
                return false;
            }

            if (! $this->completedPrerequisiteQuery($source, $prerequisiteCode, $specification)->exists()) {
                return false;
            }

            if (($specification['ownership_scope'] ?? null) === 'user'
                && $source->research_class_group_id !== null) {
                $requiredActors = ResearchClassGroupMember::query()
                    ->where('research_class_group_id', $source->research_class_group_id)
                    ->count();
                $completedActors = $this->completedPrerequisiteQuery($source, $prerequisiteCode, $specification)
                    ->distinct('initiated_by')
                    ->count('initiated_by');

                if ($requiredActors > 0 && $completedActors < $requiredActors) {
                    return false;
                }
            }
        }

        return true;
    }

    /** @param array<string, mixed> $specification */
    private function completedPrerequisiteQuery(
        OfficialFormInstance $source,
        string $code,
        array $specification,
    ): Builder {
        return OfficialFormInstance::query()
            ->whereHas('definition', fn (Builder $query) => $query->where('code', strtoupper($code)))
            ->where('status', $this->terminalStatus($specification))
            ->when(
                $source->research_class_group_id !== null,
                fn (Builder $query) => $query->where('research_class_group_id', $source->research_class_group_id),
                fn (Builder $query) => $query->where('research_class_id', $source->research_class_id),
            );
    }

    /**
     * @param  array<string, mixed>  $specification
     */
    private function notifyRecipients(
        User $actor,
        OfficialFormInstance $source,
        string $nextCode,
        array $specification,
    ): void {
        $researchClass = $this->researchClassFor($source);
        $group = $source->group;
        $firstAction = collect($specification['actions'])->first();
        $responsibleActorType = is_array($firstAction) ? ($firstAction['actor_type'] ?? null) : null;
        $recipients = $this->responsibleUsers($responsibleActorType, $group, $researchClass);

        if ($researchClass?->facilitator !== null) {
            $recipients->push($researchClass->facilitator);
        }

        $recipients = $recipients
            ->filter(fn (mixed $recipient): bool => $recipient instanceof User)
            ->unique(fn (User $recipient): int => (int) $recipient->id)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $formCode = strtoupper($nextCode);
        $context = $group?->name ?? $researchClass?->name ?? 'Research workflow';
        $existing = $this->existingNextInstance($source, $formCode);
        $routeName = $existing === null
            ? 'official-forms.workspace.index'
            : 'official-forms.workspace.show';
        $routeParameters = $existing === null ? [] : ['instance' => $existing->id];

        $this->notifications->sendToMany(
            recipients: $recipients,
            eventKey: 'official-form.next-required',
            title: "{$context} requires {$formCode}",
            message: strtoupper((string) $source->definition?->code)." is complete. {$formCode} — {$specification['title']} is now the next required form.",
            category: 'form',
            routeName: $routeName,
            routeParameters: $routeParameters,
            sourceType: OfficialFormInstance::class,
            sourceId: $source->id,
            actor: $actor,
            contextLabel: $context,
            actingAs: 'Official Forms',
            occurrence: "unlocked:{$nextCode}",
        );
    }

    private function researchClassFor(OfficialFormInstance $instance): ?ResearchClass
    {
        return $instance->researchClass ?? $instance->group?->researchClass;
    }

    /** @return Collection<int, User> */
    private function responsibleUsers(
        ?string $actorType,
        ?ResearchClassGroup $group,
        ?ResearchClass $researchClass,
    ): Collection {
        if ($actorType === null) {
            return collect();
        }

        if ($actorType === 'student_researcher' && $group !== null) {
            return User::query()
                ->whereIn('id', ResearchClassGroupMember::query()
                    ->where('research_class_group_id', $group->id)
                    ->select('student_id'))
                ->get();
        }

        if ($actorType === 'adviser' && $group?->adviser_id !== null) {
            return User::query()->whereKey($group->adviser_id)->get();
        }

        if ($actorType === 'research_facilitator' && $researchClass?->facilitator_id !== null) {
            return User::query()->whereKey($researchClass->facilitator_id)->get();
        }

        if ($researchClass === null) {
            return collect();
        }

        return User::query()
            ->whereIn('id', ResearchClassActorAssignment::query()
                ->where('research_class_id', $researchClass->id)
                ->where('actor_type', $actorType)
                ->where('status', 'active')
                ->select('user_id'))
            ->get();
    }

    private function existingNextInstance(OfficialFormInstance $source, string $code): ?OfficialFormInstance
    {
        return OfficialFormInstance::query()
            ->whereHas('definition', fn (Builder $query) => $query->where('code', $code))
            ->when(
                $source->research_class_group_id !== null,
                fn (Builder $query) => $query->where('research_class_group_id', $source->research_class_group_id),
                fn (Builder $query) => $query->where('research_class_id', $source->research_class_id),
            )
            ->latest('id')
            ->first();
    }

    /** @param array<string, mixed> $specification */
    private function terminalStatus(array $specification): string
    {
        $lastAction = collect($specification['actions'])->last();

        return is_array($lastAction) ? (string) $lastAction['to_state'] : '';
    }
}
