<?php

namespace App\Modules\Evaluations\Actions;

use App\Models\AuditLog;
use App\Models\DefenseEvaluation;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseEvaluationStudentScore;
use App\Models\DefenseEvaluationStudentSummary;
use App\Models\DefenseEvaluationSummary;
use App\Models\DefenseSchedule;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormVersion;
use App\Models\User;
use App\Modules\Evaluations\Services\EvaluationAuthorization;
use App\Notifications\AcademicWorkflowNotification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubmitDefenseEvaluation
{
    private const PROHIBITED_KEYS = [
        'panelist_user_id', 'defense_id', 'defense_schedule_id', 'research_class_group_id',
        'round_status', 'evaluation_status', 'research_paper_total', 'presentation_total',
        'panel_average', 'submitted_at', 'released_at', 'released_by', 'finalized_at',
        'summary_signer_user_id', 'completed_at', 'completed_by', 'source_type', 'source_id', 'status',
    ];

    public function __construct(
        private readonly EvaluationAuthorization $auth = new EvaluationAuthorization
    ) {}

    /**
     * @param  array{
     *     research_quality_score: float|int,
     *     originality_score: float|int,
     *     relevance_score: float|int,
     *     general_comments?: string|null,
     *     recommendations?: string|null,
     *     student_scores: array<int, array{
     *         communication_score: float|int,
     *         organization_score: float|int,
     *         effectiveness_score: float|int,
     *     }>
     * }  $data
     */
    public function handle(User $panelist, DefenseEvaluationRound $round, array $data): DefenseEvaluation
    {
        $this->auth->assertFacultyActor($panelist);

        foreach (self::PROHIBITED_KEYS as $prohibitedKey) {
            if (array_key_exists($prohibitedKey, $data)) {
                throw new InvalidArgumentException("Prohibited field [{$prohibitedKey}] in evaluation submission payload.");
            }
        }

        return DB::transaction(function () use ($panelist, $round, $data) {
            /** @var DefenseEvaluationRound $lockedRound */
            $lockedRound = DefenseEvaluationRound::query()->lockForUpdate()->with(['roundPanelists', 'roundStudents', 'defense.group'])->findOrFail($round->id);

            $roundPanelist = $this->auth->assertEligiblePanelist($panelist, $lockedRound, 'evaluations.create');

            if (! in_array($lockedRound->status, ['open', 'in_progress'], true)) {
                throw new InvalidArgumentException('Cannot submit evaluation for a round that is not open or in progress.');
            }

            $existingEval = DefenseEvaluation::query()
                ->where('defense_evaluation_round_id', $lockedRound->id)
                ->where('panelist_user_id', $panelist->id)
                ->first();

            if ($existingEval && $existingEval->status === 'submitted') {
                throw new InvalidArgumentException('Evaluation has already been submitted and is immutable.');
            }

            // Require all Research Paper criteria
            $quality = $this->requireScore($data['research_quality_score'] ?? null, 'research_quality_score');
            $originality = $this->requireScore($data['originality_score'] ?? null, 'originality_score');
            $relevance = $this->requireScore($data['relevance_score'] ?? null, 'relevance_score');

            $paperTotal = round(($quality * 0.50) + ($originality * 0.25) + ($relevance * 0.25), 2);

            $evaluation = DefenseEvaluation::query()->updateOrCreate(
                [
                    'defense_evaluation_round_id' => $lockedRound->id,
                    'panelist_user_id' => $panelist->id,
                ],
                [
                    'round_panelist_id' => $roundPanelist->id,
                    'status' => 'submitted',
                    'research_quality_score' => $quality,
                    'originality_score' => $originality,
                    'relevance_score' => $relevance,
                    'research_paper_total' => $paperTotal,
                    'general_comments' => isset($data['general_comments']) ? trim((string) $data['general_comments']) : null,
                    'recommendations' => isset($data['recommendations']) ? trim((string) $data['recommendations']) : null,
                    'submitted_at' => now(),
                ]
            );

            // Require scores for every frozen student roster member (exact match, no extra/unknown keys)
            $frozenStudentIds = $lockedRound->roundStudents->pluck('student_id')->map(fn ($id) => (int) $id)->all();
            if (empty($frozenStudentIds)) {
                throw new InvalidArgumentException('Evaluation round has no frozen student roster.');
            }

            $studentScoresInput = $data['student_scores'] ?? [];
            $submittedStudentIds = array_map(fn ($id) => (int) $id, array_keys($studentScoresInput));

            foreach ($submittedStudentIds as $submittedStudentId) {
                if (! in_array($submittedStudentId, $frozenStudentIds, true)) {
                    throw new InvalidArgumentException("Unknown student ID [{$submittedStudentId}] in submitted evaluation scores.");
                }
            }

            foreach ($frozenStudentIds as $frozenStudentId) {
                if (! in_array($frozenStudentId, $submittedStudentIds, true)) {
                    throw new InvalidArgumentException("Missing evaluation scores for student #{$frozenStudentId}.");
                }
            }

            foreach ($lockedRound->roundStudents as $roundStudent) {
                $studentInput = $studentScoresInput[$roundStudent->student_id];

                $comm = $this->requireScore($studentInput['communication_score'] ?? null, "student #{$roundStudent->student_id} communication_score");
                $org = $this->requireScore($studentInput['organization_score'] ?? null, "student #{$roundStudent->student_id} organization_score");
                $eff = $this->requireScore($studentInput['effectiveness_score'] ?? null, "student #{$roundStudent->student_id} effectiveness_score");

                $presentationTotal = round(($comm * 0.20) + ($org * 0.30) + ($eff * 0.50), 2);

                DefenseEvaluationStudentScore::query()->updateOrCreate(
                    [
                        'defense_evaluation_id' => $evaluation->id,
                        'student_id' => $roundStudent->student_id,
                    ],
                    [
                        'round_student_id' => $roundStudent->id,
                        'communication_score' => $comm,
                        'organization_score' => $org,
                        'effectiveness_score' => $eff,
                        'presentation_total' => $presentationTotal,
                    ]
                );
            }

            $evaluation->load('studentScores');

            // Create RES-036 form instance for panelist evaluation record
            $this->createRes036Instance($panelist, $lockedRound, $evaluation);

            AuditLog::query()->create([
                'user_id' => $panelist->id,
                'actor_name' => $panelist->name,
                'actor_email' => $panelist->email,
                'event' => 'evaluation.submitted',
                'auditable_type' => DefenseEvaluation::class,
                'auditable_id' => $evaluation->id,
                'description' => "Submitted defense evaluation for round #{$lockedRound->id}.",
            ]);

            // Check if all 3 panelists submitted
            $submittedCount = DefenseEvaluation::query()
                ->where('defense_evaluation_round_id', $lockedRound->id)
                ->where('status', 'submitted')
                ->count();

            if ($submittedCount === 3) {
                $lockedRound->update([
                    'status' => 'complete',
                    'all_submitted_at' => now(),
                ]);

                $this->generateSummaryAndRes037($lockedRound);
            } elseif ($lockedRound->status === 'open') {
                $lockedRound->update(['status' => 'in_progress']);
            }

            return $evaluation->load(['studentScores.roundStudent', 'round']);
        });
    }

    private function requireScore(mixed $value, string $field): float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            throw new InvalidArgumentException("Missing required score for {$field}.");
        }

        $num = (float) $value;
        if ($num < 0.00 || $num > 100.00) {
            throw new InvalidArgumentException("Score for {$field} must be between 0 and 100.");
        }

        return round($num, 2);
    }

    private function createRes036Instance(User $panelist, DefenseEvaluationRound $round, DefenseEvaluation $evaluation): void
    {
        $def = OfficialFormDefinition::query()->where('code', 'RES-036')->first();
        if (! $def) {
            return;
        }

        $existing036 = OfficialFormInstance::query()
            ->where('official_form_definition_id', $def->id)
            ->where('defense_evaluation_id', $evaluation->id)
            ->first();

        if ($existing036) {
            return;
        }

        $schedule = DefenseSchedule::query()->with('room')->find($round->defense_schedule_id);

        $instance = OfficialFormInstance::query()->create([
            'official_form_definition_id' => $def->id,
            'research_class_group_id' => $round->research_class_group_id,
            'research_class_id' => null,
            'context_key' => "evaluation-panelist-{$panelist->id}",
            'source_type' => DefenseSchedule::class,
            'source_id' => $round->defense_schedule_id,
            'defense_evaluation_id' => $evaluation->id,
            'initiated_by' => $panelist->id,
            'status' => 'submitted',
        ]);

        $version = OfficialFormVersion::query()->create([
            'official_form_instance_id' => $instance->id,
            'version_number' => 1,
            'payload' => [
                'research_quality_score' => $evaluation->research_quality_score,
                'originality_score' => $evaluation->originality_score,
                'relevance_score' => $evaluation->relevance_score,
                'research_paper_total' => $evaluation->research_paper_total,
                'general_comments' => $evaluation->general_comments,
                'recommendations' => $evaluation->recommendations,
                'student_scores' => $evaluation->studentScores->map(fn ($s) => [
                    'student_id' => $s->student_id,
                    'communication_score' => $s->communication_score,
                    'organization_score' => $s->organization_score,
                    'effectiveness_score' => $s->effectiveness_score,
                    'presentation_total' => $s->presentation_total,
                ])->toArray(),
            ],
            'source_snapshot' => [
                'defense_type' => $round->defense->defense_type,
                'starts_at' => $schedule?->starts_at?->toIso8601String(),
                'ends_at' => $schedule?->ends_at?->toIso8601String(),
                'room_code' => $schedule?->room?->code,
                'research_title' => $round->defense->group?->title ?? $round->defense->group?->name ?? 'Untitled Research',
            ],
            'created_by' => $panelist->id,
            'is_current' => true,
        ]);

        $instance->update(['current_version_id' => $version->id]);
    }

    public function generateSummaryAndRes037(DefenseEvaluationRound $round): void
    {
        $evaluations = DefenseEvaluation::query()
            ->where('defense_evaluation_round_id', $round->id)
            ->where('status', 'submitted')
            ->with('studentScores')
            ->get();

        $paperAvg = round($evaluations->avg('research_paper_total'), 2);

        $summary = DefenseEvaluationSummary::query()->updateOrCreate(
            ['defense_evaluation_round_id' => $round->id],
            [
                'research_paper_average' => $paperAvg,
                'status' => 'calculated',
            ]
        );

        foreach ($round->roundStudents as $roundStudent) {
            $studentPresentationTotals = [];
            foreach ($evaluations as $eval) {
                $score = $eval->studentScores->firstWhere('student_id', $roundStudent->student_id);
                if ($score && $score->presentation_total !== null) {
                    $studentPresentationTotals[] = (float) $score->presentation_total;
                }
            }

            $presAvg = count($studentPresentationTotals) > 0
                ? round(array_sum($studentPresentationTotals) / count($studentPresentationTotals), 2)
                : 0.00;

            DefenseEvaluationStudentSummary::query()->updateOrCreate(
                [
                    'defense_evaluation_summary_id' => $summary->id,
                    'student_id' => $roundStudent->student_id,
                ],
                [
                    'round_student_id' => $roundStudent->id,
                    'presentation_average' => $presAvg,
                ]
            );
        }

        // Generate RES-037 Form Instance
        $def037 = OfficialFormDefinition::query()->where('code', 'RES-037')->first();
        if ($def037) {
            $existing037 = OfficialFormInstance::query()
                ->where('official_form_definition_id', $def037->id)
                ->where('source_type', DefenseEvaluationRound::class)
                ->where('source_id', $round->id)
                ->first();

            $initiatorId = $round->opened_by ?? $round->summary_signer_user_id
                ?? $round->defense?->group?->researchClass?->facilitator_id;

            if (! $existing037) {
                $existing037 = OfficialFormInstance::query()->create([
                    'official_form_definition_id' => $def037->id,
                    'research_class_group_id' => $round->research_class_group_id,
                    'research_class_id' => null,
                    'context_key' => "evaluation-summary-round-{$round->id}",
                    'source_type' => DefenseEvaluationRound::class,
                    'source_id' => $round->id,
                    'initiated_by' => $initiatorId,
                    'status' => 'draft',
                ]);
            }

            // Always ensure actors are assigned — idempotent for both new and orphan instances
            if ($round->summary_signer_user_id) {
                OfficialFormActorAssignment::query()->firstOrCreate(
                    [
                        'official_form_instance_id' => $existing037->id,
                        'user_id' => $round->summary_signer_user_id,
                        'actor_type' => 'panel_chair',
                    ],
                    [
                        'status' => 'active',
                        'assigned_at' => now(),
                    ]
                );
            }

            $facilitatorId = $round->defense?->group?->researchClass?->facilitator_id;
            if ($facilitatorId) {
                OfficialFormActorAssignment::query()->firstOrCreate(
                    [
                        'official_form_instance_id' => $existing037->id,
                        'user_id' => $facilitatorId,
                        'actor_type' => 'facilitator',
                    ],
                    [
                        'status' => 'active',
                        'assigned_at' => now(),
                    ]
                );
            }

            // Create the version only if one does not yet exist (handles orphan instances too)
            if ($existing037->current_version_id === null) {
                $studentSummaries = DefenseEvaluationStudentSummary::query()
                    ->where('defense_evaluation_summary_id', $summary->id)
                    ->with('student')
                    ->get();

                $ver037 = OfficialFormVersion::query()->create([
                    'official_form_instance_id' => $existing037->id,
                    'version_number' => 1,
                    'payload' => [
                        'research_paper_average' => $summary->research_paper_average,
                        'student_summaries' => $studentSummaries->map(fn ($s) => [
                            'student_id' => $s->student_id,
                            'student_name' => $s->student?->name ?? "Student #{$s->student_id}",
                            'presentation_average' => $s->presentation_average,
                        ])->toArray(),
                        'panelist_evaluations' => $evaluations->map(fn ($e) => [
                            'panelist_user_id' => $e->panelist_user_id,
                            'research_paper_total' => $e->research_paper_total,
                            'student_scores' => $e->studentScores->map(fn ($s) => [
                                'student_id' => $s->student_id,
                                'presentation_total' => $s->presentation_total,
                            ])->toArray(),
                        ])->toArray(),
                    ],
                    'source_snapshot' => [
                        'defense_id' => $round->defense_id,
                        'defense_schedule_id' => $round->defense_schedule_id,
                        'group_id' => $round->research_class_group_id,
                        'summary_signer_user_id' => $round->summary_signer_user_id,
                    ],
                    'created_by' => $initiatorId,
                    'is_current' => true,
                ]);

                $existing037->update(['current_version_id' => $ver037->id]);

                // Notify the Panel Chair that RES-037 is ready for their signature
                if ($round->summary_signer_user_id) {
                    $panelChair = User::find($round->summary_signer_user_id);
                    if ($panelChair) {
                        $panelChair->notify(new AcademicWorkflowNotification(
                            eventKey: 'res_037_ready_for_signing',
                            title: 'Defense Evaluation Summary Ready for Signing',
                            message: 'The RES-037 Evaluation Summary sheet for the research defense has been generated and requires your signature as Panel Chairperson.',
                            category: 'official_form',
                            logicalKey: "res-037-instance-{$existing037->id}",
                            routeName: 'official-forms.workspace.show',
                            routeParameters: ['instance' => $existing037->id],
                            actingAs: 'Panel Chairperson',
                            sourceType: 'official_form_instance',
                            sourceId: $existing037->id,
                        ));
                    }
                }
            }
        }
    }
}
