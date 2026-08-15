<?php

namespace App\Modules\Evaluations\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\DefenseEvaluation;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseEvaluationStudentScore;
use App\Models\DefenseEvaluationStudentSummary;
use App\Models\DefenseEvaluationSummary;
use App\Models\DefenseSchedule;
use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormVersion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubmitDefenseEvaluation
{
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
        if ($panelist->user_type !== UserType::Faculty
            || $panelist->status !== AccountStatus::Active
            || $panelist->approved_at === null
            || $panelist->email_verified_at === null
            || ! $panelist->can('evaluations.create')
            || ! $panelist->can('forms.res-036.evaluate')) {
            throw new AuthorizationException('Unauthorized: You lack faculty credentials or permission to evaluate defenses.');
        }

        return DB::transaction(function () use ($panelist, $round, $data) {
            /** @var DefenseEvaluationRound $lockedRound */
            $lockedRound = DefenseEvaluationRound::query()->lockForUpdate()->with(['roundPanelists', 'roundStudents', 'defense.group'])->findOrFail($round->id);

            if (! in_array($lockedRound->status, ['open', 'in_progress'], true)) {
                throw new InvalidArgumentException('Cannot submit evaluation for a round that is not open or in progress.');
            }

            $roundPanelist = $lockedRound->roundPanelists->firstWhere('panelist_user_id', $panelist->id);
            if (! $roundPanelist) {
                throw new AuthorizationException('Unauthorized: You are not a frozen panelist for this evaluation round.');
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

            // Require scores for every frozen student
            $studentScoresInput = $data['student_scores'] ?? [];
            if ($lockedRound->roundStudents->isEmpty()) {
                throw new InvalidArgumentException('Evaluation round has no frozen student roster.');
            }

            foreach ($lockedRound->roundStudents as $roundStudent) {
                $studentInput = $studentScoresInput[$roundStudent->student_id] ?? null;
                if (! $studentInput) {
                    throw new InvalidArgumentException("Missing evaluation scores for student #{$roundStudent->student_id}.");
                }

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

    private function generateSummaryAndRes037(DefenseEvaluationRound $round): void
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

            if (! $existing037) {
                $inst037 = OfficialFormInstance::query()->create([
                    'official_form_definition_id' => $def037->id,
                    'research_class_group_id' => $round->research_class_group_id,
                    'research_class_id' => null,
                    'context_key' => "evaluation-summary-round-{$round->id}",
                    'source_type' => DefenseEvaluationRound::class,
                    'source_id' => $round->id,
                    'initiated_by' => $round->opened_by,
                    'status' => 'draft',
                ]);

                $studentSummaries = DefenseEvaluationStudentSummary::query()
                    ->where('defense_evaluation_summary_id', $summary->id)
                    ->with('student')
                    ->get();

                $ver037 = OfficialFormVersion::query()->create([
                    'official_form_instance_id' => $inst037->id,
                    'version_number' => 1,
                    'payload' => [
                        'research_paper_average' => $summary->research_paper_average,
                        'student_summaries' => $studentSummaries->map(fn ($s) => [
                            'student_id' => $s->student_id,
                            'student_name' => $s->student?->name ?? "Student #{$s->student_id}",
                            'presentation_average' => $s->presentation_average,
                        ])->toArray(),
                    ],
                    'source_snapshot' => [
                        'defense_id' => $round->defense_id,
                        'defense_schedule_id' => $round->defense_schedule_id,
                        'group_id' => $round->research_class_group_id,
                        'summary_signer_user_id' => $round->summary_signer_user_id,
                    ],
                    'created_by' => $round->opened_by,
                    'is_current' => true,
                ]);

                $inst037->update(['current_version_id' => $ver037->id]);
            }
        }
    }
}
