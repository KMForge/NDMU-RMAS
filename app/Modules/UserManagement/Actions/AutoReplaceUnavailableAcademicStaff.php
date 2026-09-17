<?php

namespace App\Modules\UserManagement\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefensePanelAssignment;
use App\Models\OfficialFormActorAssignment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\ResearchClassPanelCommittee;
use App\Models\ResearchClassPanelMember;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\ResearchGroupPanelMember;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reassigns current academic work when a faculty account becomes unavailable.
 * Historical signatures, completed defenses, and evaluation snapshots are never changed.
 */
class AutoReplaceUnavailableAcademicStaff
{
    /**
     * @return array{adviser_assignments: int, committee_assignments: int, defense_assignments: int, form_assignments: int}
     */
    public function handle(User $unavailable, User $actor, string $reason): array
    {
        return DB::transaction(function () use ($unavailable, $actor, $reason): array {
            $hasLockedEvaluationRoster = DefensePanelAssignment::query()
                ->where('user_id', $unavailable->id)
                ->whereNull('ended_at')
                ->whereHas('defense', fn ($defenses) => $defenses
                    ->whereIn('status', ['scheduled', 'rescheduled', 'in_progress'])
                    ->whereHas('evaluationRounds', fn ($rounds) => $rounds->whereIn('status', ['open', 'in_progress', 'complete', 'finalized', 'released'])))
                ->exists();

            if ($hasLockedEvaluationRoster) {
                throw ValidationException::withMessages([
                    'replacement' => 'This faculty account cannot be made unavailable while a defense evaluation roster is open or finalized. Complete or administratively resolve that evaluation first.',
                ]);
            }

            $counts = [
                'adviser_assignments' => 0,
                'committee_assignments' => 0,
                'defense_assignments' => 0,
                'form_assignments' => 0,
            ];

            $adviserReplacements = [];
            $panelReplacements = [];

            $groups = ResearchClassGroup::query()
                ->where('adviser_id', $unavailable->id)
                ->where('status', 'active')
                ->whereNull('disbanded_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($groups as $group) {
                $replacement = $this->selectAdviser($unavailable);
                $now = now();

                ResearchClassGroupAdviserHistory::query()
                    ->where('research_class_group_id', $group->id)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => $now, 'ended_by' => $actor->id, 'updated_at' => $now]);

                $group->update(['adviser_id' => $replacement->id]);
                ResearchClassGroupAdviserHistory::query()->create([
                    'research_class_group_id' => $group->id,
                    'adviser_id' => $replacement->id,
                    'assigned_by' => $actor->id,
                    'assigned_at' => $now,
                ]);

                $adviserReplacements[$group->id] = $replacement;
                $counts['adviser_assignments']++;
                $this->audit($actor, $unavailable, $replacement, 'research-group adviser', $group, $reason);
            }

            $classCommittees = ResearchClassPanelCommittee::query()
                ->where(function ($query) use ($unavailable): void {
                    $query->where('chairperson_id', $unavailable->id)
                        ->orWhereHas('members', fn ($members) => $members->where('user_id', $unavailable->id));
                })
                ->with('members')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($classCommittees as $committee) {
                $counts['committee_assignments'] += $this->replaceCommitteePositions(
                    $committee,
                    $unavailable,
                    $actor,
                    $reason,
                    $panelReplacements,
                );
            }

            $groupCommittees = ResearchGroupPanelCommittee::query()
                ->where(function ($query) use ($unavailable): void {
                    $query->where('chairperson_id', $unavailable->id)
                        ->orWhereHas('members', fn ($members) => $members->where('user_id', $unavailable->id));
                })
                ->with('members')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($groupCommittees as $committee) {
                $counts['committee_assignments'] += $this->replaceCommitteePositions(
                    $committee,
                    $unavailable,
                    $actor,
                    $reason,
                    $panelReplacements,
                );
            }

            $assignments = DefensePanelAssignment::query()
                ->where('user_id', $unavailable->id)
                ->whereNull('ended_at')
                ->whereHas('defense', function ($query): void {
                    $query->whereIn('status', ['scheduled', 'rescheduled'])
                        ->whereDoesntHave('evaluationRounds', fn ($rounds) => $rounds->whereIn('status', ['open', 'in_progress', 'complete', 'finalized', 'released']));
                })
                ->with('defense.currentSchedule')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($assignments as $assignment) {
                $defense = $assignment->defense;
                $excluded = DefensePanelAssignment::query()
                    ->where('defense_id', $defense->id)
                    ->whereNull('ended_at')
                    ->where('user_id', '!=', $unavailable->id)
                    ->pluck('user_id')
                    ->all();
                $replacement = $this->selectPanelist($unavailable, $excluded, $defense);

                $assignment->update(['ended_at' => now(), 'change_reason' => $reason]);
                DefensePanelAssignment::query()->create([
                    'defense_id' => $defense->id,
                    'user_id' => $replacement->id,
                    'panel_position' => $assignment->panel_position,
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                    'change_reason' => $reason,
                ]);

                $panelReplacements[$defense->id] = $replacement;
                $counts['defense_assignments']++;
                $this->audit($actor, $unavailable, $replacement, 'defense panel', $defense, $reason);
            }

            $formAssignments = OfficialFormActorAssignment::query()
                ->where('user_id', $unavailable->id)
                ->where('status', 'active')
                ->whereIn('actor_type', ['adviser', 'research_adviser', 'panelist'])
                ->whereHas('instance', fn ($instances) => $instances->whereIn('status', ['draft', 'submitted', 'pending', 'in_progress', 'returned_for_correction']))
                ->with(['instance.group', 'instance.source'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($formAssignments as $assignment) {
                $instance = $assignment->instance;
                $replacement = str_contains($assignment->actor_type, 'adviser')
                    ? ($adviserReplacements[$instance->research_class_group_id] ?? $instance->group?->adviser)
                    : $this->replacementForFormPanel($instance, $panelReplacements, $unavailable);

                if (! $replacement || (int) $replacement->id === (int) $unavailable->id) {
                    $replacement = str_contains($assignment->actor_type, 'adviser')
                        ? $this->selectAdviser($unavailable)
                        : $this->selectPanelist($unavailable);
                }

                $assignment->update(['status' => 'inactive']);
                OfficialFormActorAssignment::query()->updateOrCreate(
                    [
                        'official_form_instance_id' => $instance->id,
                        'actor_type' => $assignment->actor_type,
                        'user_id' => $replacement->id,
                    ],
                    [
                        'assigned_by' => $actor->id,
                        'assigned_at' => now(),
                        'status' => 'active',
                    ],
                );

                $counts['form_assignments']++;
                $this->audit($actor, $unavailable, $replacement, 'pending official-form actor', $instance, $reason);
            }

            return $counts;
        }, 3);
    }

    private function replaceCommitteePositions(
        ResearchClassPanelCommittee|ResearchGroupPanelCommittee $committee,
        User $unavailable,
        User $actor,
        string $reason,
        array &$panelReplacements,
    ): int {
        $replaced = 0;
        $excluded = $committee->members->pluck('user_id')->push($committee->chairperson_id)
            ->reject(fn ($id) => (int) $id === (int) $unavailable->id)
            ->map(fn ($id) => (int) $id)
            ->all();

        if ((int) $committee->chairperson_id === (int) $unavailable->id) {
            $replacement = $this->selectPanelist($unavailable, $excluded);
            $committee->update(['chairperson_id' => $replacement->id, 'updated_by' => $actor->id]);
            $excluded[] = $replacement->id;
            $panelReplacements['committee:'.$committee::class.':'.$committee->id] = $replacement;
            $this->audit($actor, $unavailable, $replacement, 'committee chairperson', $committee, $reason);
            $replaced++;
        }

        foreach ($committee->members->where('user_id', $unavailable->id) as $member) {
            $replacement = $this->selectPanelist($unavailable, $excluded);
            $member->update(['user_id' => $replacement->id]);
            $excluded[] = $replacement->id;
            $panelReplacements['committee:'.$committee::class.':'.$committee->id] = $replacement;
            $this->audit($actor, $unavailable, $replacement, 'committee '.$member->panel_position, $committee, $reason);
            $replaced++;
        }

        return $replaced;
    }

    private function selectAdviser(User $unavailable): User
    {
        return $this->rankedCandidates('classes.serve-as-adviser', $unavailable)
            ->sort(fn (User $left, User $right) => $this->compareCandidates($left, $right, $unavailable, true))
            ->first()
            ?? throw ValidationException::withMessages([
                'replacement' => 'The account cannot be made unavailable because no active, verified replacement adviser is eligible.',
            ]);
    }

    /** @param list<int> $excluded */
    private function selectPanelist(User $unavailable, array $excluded = [], ?Defense $defense = null): User
    {
        $candidates = $this->rankedCandidates('evaluations.create', $unavailable, $excluded);
        if ($defense?->currentSchedule) {
            $schedule = $defense->currentSchedule;
            $candidates = $candidates->reject(function (User $candidate) use ($defense, $schedule): bool {
                return DefensePanelAssignment::query()
                    ->where('user_id', $candidate->id)
                    ->whereNull('ended_at')
                    ->where('defense_id', '!=', $defense->id)
                    ->whereHas('defense.currentSchedule', fn ($query) => $query
                        ->where('status', 'current')
                        ->where('starts_at', '<', $schedule->ends_at)
                        ->where('ends_at', '>', $schedule->starts_at))
                    ->exists();
            });
        }

        return $candidates
            ->sort(fn (User $left, User $right) => $this->compareCandidates($left, $right, $unavailable, false))
            ->first()
            ?? throw ValidationException::withMessages([
                'replacement' => 'The account cannot be made unavailable because no active, verified replacement panelist is eligible without a roster or schedule conflict.',
            ]);
    }

    /** @param list<int> $excluded */
    private function rankedCandidates(string $permission, User $unavailable, array $excluded = []): Collection
    {
        return User::query()
            ->permission($permission)
            ->with('facultyProfile')
            ->where('user_type', UserType::Faculty)
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->whereNotNull('email_verified_at')
            ->whereKeyNot($unavailable->id)
            ->when($excluded !== [], fn ($query) => $query->whereNotIn('id', $excluded))
            ->get();
    }

    private function compareCandidates(User $left, User $right, User $unavailable, bool $adviser): int
    {
        $leftRank = [$this->sameDepartment($left, $unavailable) ? 0 : 1, $this->assignmentLoad($left, $adviser), $left->id];
        $rightRank = [$this->sameDepartment($right, $unavailable) ? 0 : 1, $this->assignmentLoad($right, $adviser), $right->id];

        return $leftRank <=> $rightRank;
    }

    private function sameDepartment(User $candidate, User $unavailable): bool
    {
        $candidateDepartmentId = $candidate->facultyProfile?->department_id;
        $unavailable->loadMissing('facultyProfile');
        $unavailableDepartmentId = $unavailable->facultyProfile?->department_id;

        if ($candidateDepartmentId && $unavailableDepartmentId) {
            return (int) $candidateDepartmentId === (int) $unavailableDepartmentId;
        }

        return filled($candidate->department)
            && mb_strtolower(trim((string) $candidate->department)) === mb_strtolower(trim((string) $unavailable->department));
    }

    private function assignmentLoad(User $candidate, bool $adviser): int
    {
        if ($adviser) {
            return ResearchClassGroup::query()
                ->where('adviser_id', $candidate->id)
                ->where('status', 'active')
                ->whereNull('disbanded_at')
                ->count();
        }

        return DefensePanelAssignment::query()->where('user_id', $candidate->id)->whereNull('ended_at')->count()
            + ResearchClassPanelCommittee::query()->where('chairperson_id', $candidate->id)->count()
            + ResearchClassPanelMember::query()->where('user_id', $candidate->id)->count()
            + ResearchGroupPanelCommittee::query()->where('chairperson_id', $candidate->id)->count()
            + ResearchGroupPanelMember::query()->where('user_id', $candidate->id)->count();
    }

    private function replacementForFormPanel($instance, array $panelReplacements, User $unavailable): ?User
    {
        $source = $instance->source;
        if ($source && isset($source->defense_id)) {
            if (isset($panelReplacements[$source->defense_id])) {
                return $panelReplacements[$source->defense_id];
            }

            return DefensePanelAssignment::query()
                ->where('defense_id', $source->defense_id)
                ->where('user_id', '!=', $unavailable->id)
                ->whereNull('ended_at')
                ->with('user')
                ->first()?->user;
        }

        return null;
    }

    private function audit(User $actor, User $outgoing, User $incoming, string $assignmentType, $subject, string $reason): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'event' => 'academic-assignment.auto-replaced',
            'auditable_type' => $subject::class,
            'auditable_id' => $subject->getKey(),
            'description' => "Automatically replaced {$outgoing->name} with {$incoming->name} as {$assignmentType}.",
            'old_values' => ['user_id' => $outgoing->id, 'name' => $outgoing->name],
            'new_values' => ['user_id' => $incoming->id, 'name' => $incoming->name, 'reason' => $reason],
        ]);
    }
}
