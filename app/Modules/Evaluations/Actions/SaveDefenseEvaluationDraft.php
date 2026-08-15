<?php

namespace App\Modules\Evaluations\Actions;

use App\Models\AuditLog;
use App\Models\DefenseEvaluation;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseEvaluationStudentScore;
use App\Models\User;
use App\Modules\Evaluations\Services\EvaluationAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaveDefenseEvaluationDraft
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
     *     research_quality_score?: float|int|null,
     *     originality_score?: float|int|null,
     *     relevance_score?: float|int|null,
     *     general_comments?: string|null,
     *     recommendations?: string|null,
     *     student_scores?: array<int, array{
     *         communication_score?: float|int|null,
     *         organization_score?: float|int|null,
     *         effectiveness_score?: float|int|null,
     *     }>
     * }  $data
     */
    public function handle(User $panelist, DefenseEvaluationRound $round, array $data): DefenseEvaluation
    {
        $this->auth->assertFacultyActor($panelist);

        foreach (self::PROHIBITED_KEYS as $prohibitedKey) {
            if (array_key_exists($prohibitedKey, $data)) {
                throw new InvalidArgumentException("Prohibited field [{$prohibitedKey}] in draft evaluation payload.");
            }
        }

        return DB::transaction(function () use ($panelist, $round, $data) {
            /** @var DefenseEvaluationRound $lockedRound */
            $lockedRound = DefenseEvaluationRound::query()->lockForUpdate()->with(['roundPanelists', 'roundStudents'])->findOrFail($round->id);

            $roundPanelist = $this->auth->assertEligiblePanelist($panelist, $lockedRound, 'evaluations.create');

            if (! in_array($lockedRound->status, ['open', 'in_progress'], true)) {
                throw new InvalidArgumentException('Cannot save draft for an evaluation round that is not open or in progress.');
            }

            $existingEval = DefenseEvaluation::query()
                ->where('defense_evaluation_round_id', $lockedRound->id)
                ->where('panelist_user_id', $panelist->id)
                ->first();

            if ($existingEval && $existingEval->status === 'submitted') {
                throw new InvalidArgumentException('Cannot modify an evaluation that has already been submitted.');
            }

            // Clean & validate Research Paper scores (0-100)
            $quality = $this->parseScore($data['research_quality_score'] ?? null, 'research_quality_score');
            $originality = $this->parseScore($data['originality_score'] ?? null, 'originality_score');
            $relevance = $this->parseScore($data['relevance_score'] ?? null, 'relevance_score');

            $paperTotal = null;
            if ($quality !== null && $originality !== null && $relevance !== null) {
                $paperTotal = round(($quality * 0.50) + ($originality * 0.25) + ($relevance * 0.25), 2);
            }

            $evaluation = DefenseEvaluation::query()->updateOrCreate(
                [
                    'defense_evaluation_round_id' => $lockedRound->id,
                    'panelist_user_id' => $panelist->id,
                ],
                [
                    'round_panelist_id' => $roundPanelist->id,
                    'status' => 'draft',
                    'research_quality_score' => $quality,
                    'originality_score' => $originality,
                    'relevance_score' => $relevance,
                    'research_paper_total' => $paperTotal,
                    'general_comments' => isset($data['general_comments']) ? trim((string) $data['general_comments']) : null,
                    'recommendations' => isset($data['recommendations']) ? trim((string) $data['recommendations']) : null,
                ]
            );

            // Clean & validate Student Presentation scores
            $frozenStudentIds = $lockedRound->roundStudents->pluck('student_id')->map(fn ($id) => (int) $id)->all();
            $studentScoresInput = $data['student_scores'] ?? [];

            // Reject unknown student IDs
            foreach (array_keys($studentScoresInput) as $submittedStudentId) {
                if (! in_array((int) $submittedStudentId, $frozenStudentIds, true)) {
                    throw new InvalidArgumentException("Unknown student ID [{$submittedStudentId}] in draft evaluation scores.");
                }
            }

            foreach ($lockedRound->roundStudents as $roundStudent) {
                $studentInput = $studentScoresInput[$roundStudent->student_id] ?? null;

                $comm = $this->parseScore($studentInput['communication_score'] ?? null, "student #{$roundStudent->student_id} communication_score");
                $org = $this->parseScore($studentInput['organization_score'] ?? null, "student #{$roundStudent->student_id} organization_score");
                $eff = $this->parseScore($studentInput['effectiveness_score'] ?? null, "student #{$roundStudent->student_id} effectiveness_score");

                $presentationTotal = null;
                if ($comm !== null && $org !== null && $eff !== null) {
                    $presentationTotal = round(($comm * 0.20) + ($org * 0.30) + ($eff * 0.50), 2);
                }

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

            if ($lockedRound->status === 'open') {
                $lockedRound->update(['status' => 'in_progress']);
            }

            AuditLog::query()->create([
                'user_id' => $panelist->id,
                'actor_name' => $panelist->name,
                'actor_email' => $panelist->email,
                'event' => 'evaluation.draft_saved',
                'auditable_type' => DefenseEvaluation::class,
                'auditable_id' => $evaluation->id,
                'description' => "Saved draft evaluation for round #{$lockedRound->id}.",
            ]);

            return $evaluation->load(['studentScores.roundStudent']);
        });
    }

    private function parseScore(mixed $value, string $field): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Score for {$field} must be numeric.");
        }

        $num = (float) $value;
        if ($num < 0.00 || $num > 100.00) {
            throw new InvalidArgumentException("Score for {$field} must be between 0 and 100.");
        }

        return round($num, 2);
    }
}
