<?php

namespace App\Modules\DefenseScheduling\Queries;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\DefenseSchedule;
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

        $schedules = $query->orderBy('starts_at', 'asc')->get();

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

            return [
                'id' => $schedule->id,
                'defense_id' => $defense?->id,
                'defense_type' => $defense?->defense_type,
                'defense_type_label' => $defenseTypeLabel,
                'schedule_status' => $schedule->status,
                'defense_status' => $defense?->status,
                'is_current' => $schedule->status === 'current' && $defense?->current_schedule_id === $schedule->id,
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
                'can_initiate_res036' => $isPanelist && $schedule->status === 'current' && $defense?->status === 'scheduled' && $user->hasPermissionTo('forms.res-036.evaluate'),
                'res036_url' => route('official-forms.workspace.store-from-source', [
                    'definition' => 'res-036',
                    'sourceKind' => 'defense-schedule',
                    'source' => $schedule->id,
                ]),
            ];
        });
    }
}
