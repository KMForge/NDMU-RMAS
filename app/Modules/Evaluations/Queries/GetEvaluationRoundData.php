<?php

namespace App\Modules\Evaluations\Queries;

use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroupMember;
use App\Models\User;

class GetEvaluationRoundData
{
    /**
     * Get evaluation round details scoped by user role and privacy rules.
     *
     * @return array<string, mixed>
     */
    public function executeForUser(User $user, ?Defense $defense = null): array
    {
        if ($user->can('evaluations.release') || $user->can('defenses.manage')) {
            return $this->forFacilitator($user, $defense);
        }

        if ($user->hasRole('thesis-adviser') || $user->can('evaluations.view-assigned')) {
            return $this->forAdviser($user, $defense);
        }

        if ($user->can('evaluations.create') || $user->can('forms.res-036.evaluate')) {
            return $this->forPanelist($user, $defense);
        }

        return $this->forStudent($user, $defense);
    }

    /**
     * Facilitator view: all rounds in owned classes.
     *
     * @return array<string, mixed>
     */
    public function forFacilitator(User $facilitator, ?Defense $defense = null): array
    {
        $query = DefenseEvaluationRound::query()
            ->with([
                'defense.group.researchClass',
                'defenseSchedule.room',
                'roundPanelists.panelist',
                'roundStudents.student',
                'summary.studentSummaries.student',
                'summarySigner',
                'evaluations',
            ])
            ->whereHas('defense.group.researchClass', function ($q) use ($facilitator) {
                $q->where('facilitator_id', $facilitator->id);
            });

        if ($defense) {
            $query->where('defense_id', $defense->id);
        }

        $rounds = $query->latest('opened_at')->get();

        return [
            'rounds' => $rounds->map(function ($round) {
                $res037Instance = OfficialFormInstance::query()
                    ->where('source_type', DefenseEvaluationRound::class)
                    ->where('source_id', $round->id)
                    ->with('currentVersion.signatures')
                    ->first();

                $is037Signed = false;
                if ($res037Instance && $res037Instance->currentVersion && $round->summary_signer_user_id) {
                    $is037Signed = $res037Instance->currentVersion->signatures()
                        ->where('signer_user_id', $round->summary_signer_user_id)
                        ->where('signature_type', 'formal_signature')
                        ->exists();
                }

                return [
                    'id' => $round->id,
                    'defense_id' => $round->defense_id,
                    'defense_type' => $round->defense->defense_type,
                    'defense_status' => $round->defense->status,
                    'group_id' => $round->research_class_group_id,
                    'group_name' => $round->defense->group?->name,
                    'research_title' => $round->defense->group?->title ?? $round->defense->group?->name,
                    'status' => $round->status,
                    'opened_at' => $round->opened_at?->toIso8601String(),
                    'all_submitted_at' => $round->all_submitted_at?->toIso8601String(),
                    'finalized_at' => $round->finalized_at?->toIso8601String(),
                    'released_at' => $round->released_at?->toIso8601String(),
                    'summary_signer_user_id' => $round->summary_signer_user_id,
                    'summary_signer_name' => $round->summarySigner?->name,
                    'is_res037_signed' => $is037Signed,
                    'res037_instance_id' => $res037Instance?->id,
                    'panelists' => $round->roundPanelists->map(fn ($p) => [
                        'user_id' => $p->panelist_user_id,
                        'name' => $p->panelist?->name,
                        'position' => $p->position,
                        'has_submitted' => $round->evaluations->contains(fn ($e) => (int) $e->panelist_user_id === (int) $p->panelist_user_id && $e->status === 'submitted'),
                    ])->values()->all(),
                    'students' => $round->roundStudents->map(fn ($s) => [
                        'user_id' => $s->student_id,
                        'name' => $s->student_name_snapshot,
                    ])->values()->all(),
                    'summary' => $round->summary ? [
                        'research_paper_average' => $round->summary->research_paper_average,
                        'student_summaries' => $round->summary->studentSummaries->map(fn ($ss) => [
                            'student_id' => $ss->student_id,
                            'student_name' => $ss->student?->name,
                            'presentation_average' => $ss->presentation_average,
                        ])->values()->all(),
                    ] : null,
                ];
            })->values()->all(),
        ];
    }

    /**
     * Panelist view: assigned rounds where user is a panelist.
     *
     * @return array<string, mixed>
     */
    public function forPanelist(User $panelist, ?Defense $defense = null): array
    {
        $query = DefenseEvaluationRound::query()
            ->with([
                'defense.group',
                'defenseSchedule.room',
                'roundPanelists.panelist',
                'roundStudents.student',
                'evaluations' => fn ($q) => $q->where('panelist_user_id', $panelist->id)->with('studentScores'),
            ])
            ->whereHas('roundPanelists', function ($q) use ($panelist) {
                $q->where('panelist_user_id', $panelist->id);
            });

        if ($defense) {
            $query->where('defense_id', $defense->id);
        }

        $rounds = $query->latest('opened_at')->get();

        return [
            'rounds' => $rounds->map(function ($round) use ($panelist) {
                $eval = $round->evaluations->first();

                return [
                    'id' => $round->id,
                    'defense_id' => $round->defense_id,
                    'defense_type' => $round->defense->defense_type,
                    'group_name' => $round->defense->group?->name,
                    'research_title' => $round->defense->group?->title ?? $round->defense->group?->name,
                    'status' => $round->status,
                    'opened_at' => $round->opened_at?->toIso8601String(),
                    'is_designated_signer' => (int) $round->summary_signer_user_id === (int) $panelist->id,
                    'evaluation' => $eval ? [
                        'id' => $eval->id,
                        'status' => $eval->status,
                        'research_quality_score' => $eval->research_quality_score,
                        'originality_score' => $eval->originality_score,
                        'relevance_score' => $eval->relevance_score,
                        'research_paper_total' => $eval->research_paper_total,
                        'general_comments' => $eval->general_comments,
                        'recommendations' => $eval->recommendations,
                        'submitted_at' => $eval->submitted_at?->toIso8601String(),
                        'student_scores' => $eval->studentScores->mapWithKeys(fn ($s) => [
                            $s->student_id => [
                                'communication_score' => $s->communication_score,
                                'organization_score' => $s->organization_score,
                                'effectiveness_score' => $s->effectiveness_score,
                                'presentation_total' => $s->presentation_total,
                            ],
                        ])->all(),
                    ] : null,
                    'students' => $round->roundStudents->map(fn ($s) => [
                        'user_id' => $s->student_id,
                        'name' => $s->student_name_snapshot,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * Adviser view: rounds for assigned advisee groups.
     *
     * @return array<string, mixed>
     */
    public function forAdviser(User $adviser, ?Defense $defense = null): array
    {
        $query = DefenseEvaluationRound::query()
            ->with([
                'defense.group.members.student',
                'defense.group.researchClass',
                'defenseSchedule.room',
                'roundPanelists.panelist',
                'roundStudents.student',
                'summary.studentSummaries.student',
                'evaluations.panelist',
            ])
            ->whereHas('defense.group', function ($q) use ($adviser) {
                $q->where('adviser_id', $adviser->id);
            });

        if ($defense) {
            $query->where('defense_id', $defense->id);
        }

        $rounds = $query->latest('opened_at')->get();

        return [
            'rounds' => $rounds->map(function ($round) {
                $recommendations = $round->evaluations
                    ->filter(fn ($e) => ! empty($e->recommendations) || ! empty($e->general_comments))
                    ->map(fn ($e) => [
                        'panelist_name' => $e->panelist?->name ?? 'Panel Member',
                        'recommendations' => $e->recommendations,
                        'general_comments' => $e->general_comments,
                    ])->values()->all();

                return [
                    'id' => $round->id,
                    'defense_id' => $round->defense_id,
                    'defense_type' => $round->defense->defense_type,
                    'group_id' => $round->research_class_group_id,
                    'group_name' => $round->defense->group?->name,
                    'class_name' => $round->defense->group?->researchClass?->name,
                    'research_title' => $round->defense->group?->title ?? $round->defense->group?->name,
                    'status' => $round->status,
                    'opened_at' => $round->opened_at?->toIso8601String(),
                    'finalized_at' => $round->finalized_at?->toIso8601String(),
                    'released_at' => $round->released_at?->toIso8601String(),
                    'scheduled_at' => $round->defenseSchedule?->scheduled_at?->toIso8601String(),
                    'room_name' => $round->defenseSchedule?->room?->name,
                    'panelists' => $round->roundPanelists->map(fn ($p) => [
                        'user_id' => $p->panelist_user_id,
                        'name' => $p->panelist?->name,
                        'position' => $p->position,
                        'has_submitted' => $round->evaluations->contains(fn ($e) => (int) $e->panelist_user_id === (int) $p->panelist_user_id && $e->status === 'submitted'),
                    ])->values()->all(),
                    'students' => $round->roundStudents->map(fn ($s) => [
                        'user_id' => $s->student_id,
                        'name' => $s->student_name_snapshot ?? $s->student?->name,
                    ])->values()->all(),
                    'summary' => $round->summary ? [
                        'research_paper_average' => $round->summary->research_paper_average,
                        'student_summaries' => $round->summary->studentSummaries->map(fn ($ss) => [
                            'student_id' => $ss->student_id,
                            'student_name' => $ss->student?->name,
                            'presentation_average' => $ss->presentation_average,
                        ])->values()->all(),
                    ] : null,
                    'recommendations' => $recommendations,
                ];
            })->values()->all(),
        ];
    }

    /**
     * Student view: ONLY released evaluation rounds.
     * STRICT PRIVACY: Student sees ONLY group Research Paper average + ONLY THEIR OWN Presentation average.
     *
     * @return array<string, mixed>
     */
    public function forStudent(User $student, ?Defense $defense = null): array
    {
        // Get student's group IDs via ResearchClassGroupMember
        $groupIds = ResearchClassGroupMember::query()
            ->where('student_id', $student->id)
            ->pluck('research_class_group_id')
            ->all();

        if (empty($groupIds)) {
            return ['rounds' => []];
        }

        $query = DefenseEvaluationRound::query()
            ->with([
                'defense.group',
                'summary.studentSummaries' => function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                },
            ])
            ->whereIn('research_class_group_id', $groupIds)
            ->where('status', 'released');

        if ($defense) {
            $query->where('defense_id', $defense->id);
        }

        $rounds = $query->latest('released_at')->get();

        return [
            'rounds' => $rounds->map(function ($round) use ($student) {
                $ownSummary = $round->summary?->studentSummaries->firstWhere('student_id', $student->id);

                return [
                    'id' => $round->id,
                    'defense_id' => $round->defense_id,
                    'defense_type' => $round->defense->defense_type,
                    'group_name' => $round->defense->group?->name,
                    'research_title' => $round->defense->group?->title ?? $round->defense->group?->name,
                    'released_at' => $round->released_at?->toIso8601String(),
                    'research_paper_average' => $round->summary?->research_paper_average,
                    'own_presentation_average' => $ownSummary?->presentation_average,
                ];
            })->values()->all(),
        ];
    }
}
