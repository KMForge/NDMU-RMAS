<?php

namespace App\Modules\DefenseScheduling\Queries;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseSchedule;
use App\Models\OfficialFormInstance;
use App\Models\User;
use Illuminate\Support\Collection;

class GetDefenseScheduleCalendar
{
    public function execute(User $user): Collection
    {
        if ($user->status !== AccountStatus::Active) {
            return collect();
        }

        $query = DefenseSchedule::query()
            ->with([
                'defense.group.researchClass',
                'defense.group.researchGroup.currentProject',
                'defense.activePanelAssignments.user',
                'defense.evaluationRounds.evaluations',
                'defense.evaluationRounds.summarySigner',
                'defense.titlePresentation',
                'room',
            ]);

        if ($user->user_type === UserType::Student) {
            $query->whereHas('defense.group.members', function ($q) use ($user) {
                $q->where('student_id', $user->id);
            });
        } elseif ($user->user_type === UserType::Faculty) {
            $query->where(function ($q) use ($user) {
                // Owned class facilitator
                $q->whereHas('defense.group.researchClass', function ($rq) use ($user) {
                    $rq->where('facilitator_id', $user->id);
                })
                // Adviser of group
                    ->orWhereHas('defense.group', function ($gq) use ($user) {
                        $gq->where('adviser_id', $user->id);
                    })
                // Active panel assignment
                    ->orWhereHas('defense.activePanelAssignments', function ($pq) use ($user) {
                        $pq->where('user_id', $user->id);
                    });
            });
        } elseif ($user->user_type === UserType::Admin && $user->can('research.view-all')) {
            // Admin system-wide read visibility
        } else {
            return collect();
        }

        $schedules = $query->orderByDesc('id')->get();

        return $schedules->map(function (DefenseSchedule $schedule) use ($user) {
            $defense = $schedule->defense;
            $group = $defense?->group;
            $room = $schedule->room;

            $isFacilitator = $user->user_type === UserType::Faculty
                && $group?->researchClass
                && (int) $group->researchClass->facilitator_id === (int) $user->id;

            $isPanelist = $user->user_type === UserType::Faculty
                && $defense?->activePanelAssignments->contains(fn ($p) => (int) $p->user_id === (int) $user->id);

            $panelists = $defense?->activePanelAssignments->map(function ($assignment) {
                $pUser = $assignment->user;

                return [
                    'id' => $pUser->id,
                    'name' => $pUser->name,
                    'email' => $pUser->email,
                    'position' => $assignment->panel_position,
                    'position_label' => match ($assignment->panel_position) {
                        'chairperson' => 'Chairperson',
                        'member_1' => 'Panel Member 1',
                        'member_2' => 'Panel Member 2',
                        default => 'Panel Member',
                    },
                ];
            })->values()->toArray() ?? [];

            $defenseTypeLabel = match ($defense?->defense_type) {
                'title_presentation' => 'Title Proposal',
                'proposal_defense' => 'Proposal Defense',
                'pre_final_defense' => 'Pre-Final Defense',
                'final_defense' => 'Final Oral Defense',
                default => 'Research Defense',
            };

            $round = $defense?->evaluationRounds?->firstWhere('defense_schedule_id', $schedule->id)
                ?? $defense?->evaluationRounds?->sortByDesc('id')->first();
            $evaluationsSubmittedCount = $round?->evaluations?->where('status', 'submitted')->count() ?? 0;

            $isTitlePresentationCompleted = $defense?->defense_type === 'title_presentation'
                && $defense?->titlePresentation
                && in_array($defense->titlePresentation->status, ['presented', 'awaiting_panel_signatures', 'awaiting_program_coordinator', 'awaiting_dean', 'finalized'], true);

            $isEvaluationRoundCompleted = $round !== null && in_array($round->status, ['finalized', 'released', 'complete', 'completed'], true);

            $isCompleted = $isTitlePresentationCompleted || $isEvaluationRoundCompleted || $defense?->status === 'completed' || $schedule->status === 'completed';
            $computedDefenseStatus = $isCompleted ? 'completed' : $defense?->status;
            $computedScheduleStatus = $isCompleted ? 'completed' : $schedule->status;

            $res037Instance = $round
                ? OfficialFormInstance::query()
                    ->where('source_type', DefenseEvaluationRound::class)
                    ->where('source_id', $round->id)
                    ->latest('id')
                    ->first()
                : null;

            return [
                'id' => $schedule->id,
                'defense_id' => $defense?->id,
                'defense_type' => $defense?->defense_type,
                'defense_type_label' => $defenseTypeLabel,
                'schedule_status' => $computedScheduleStatus,
                'defense_status' => $computedDefenseStatus,
                'is_current' => $schedule->status === 'current' && $defense?->current_schedule_id === $schedule->id,
                'defense_session_id' => $schedule->defense_session_id,
                'presentation_order' => $schedule->presentation_order,
                'presentation_order_label' => $schedule->presentation_order ? "Order #{$schedule->presentation_order}" : null,
                'starts_at' => $schedule->starts_at?->toIso8601String(),
                'ends_at' => $schedule->ends_at?->toIso8601String(),
                'formatted_date' => $schedule->starts_at?->format('M d, Y'),
                'formatted_time' => $schedule->starts_at?->format('h:i A').' - '.$schedule->ends_at?->format('h:i A'),
                'room_code' => $room?->code,
                'room_name' => $room?->name,
                'location_notes' => $room?->location_notes,
                'group_id' => $group?->id,
                'group_name' => $group?->name ?? 'Group #'.$group?->id,
                'research_title' => $group?->researchGroup?->currentProject?->title ?? $group?->name ?? 'Untitled Research',
                'panelists' => $panelists,
                'reason' => $schedule->reason,
                'can_manage' => $isFacilitator && $user->can('defenses.manage'),
                'has_open_round' => $round !== null && in_array($round->status, ['open', 'in_progress'], true),
                'can_initiate_res036' => $isPanelist
                    && (($round !== null && in_array($round->status, ['open', 'in_progress'], true)) || ($schedule->status === 'current' && in_array($defense?->status, ['scheduled', 'in_progress'], true)))
                    && ($round?->evaluations?->where('panelist_user_id', $user->id)->where('status', 'submitted')->isEmpty() ?? true)
                    && $user->hasPermissionTo('forms.res-036.evaluate'),
                'evaluation_round' => $round ? [
                    'id' => $round->id,
                    'status' => $round->status,
                    'summary_signer_id' => $round->summary_signer_user_id,
                    'summary_signer_name' => $round->summarySigner?->name,
                    'submitted_count' => $evaluationsSubmittedCount,
                    'res037_url' => $res037Instance ? route('official-forms.workspace.show', $res037Instance) : null,
                ] : null,
                'res036_url' => route('official-forms.workspace.store-from-source', [
                    'definition' => 'res-036',
                    'sourceKind' => 'defense-schedule',
                    'source' => $schedule->id,
                ]),
                'res037_url' => $res037Instance ? route('official-forms.workspace.show', $res037Instance) : null,
            ];
        });
    }
}
