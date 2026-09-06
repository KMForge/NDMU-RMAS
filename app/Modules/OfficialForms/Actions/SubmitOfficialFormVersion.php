<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\DefenseEvaluation;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseEvaluationStudentScore;
use App\Models\DefenseSchedule;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormVersion;
use App\Models\User;
use App\Models\UserSignature;
use App\Modules\Evaluations\Actions\SubmitDefenseEvaluation;
use App\Modules\Evaluations\Services\Res036Rubric;
use App\Modules\OfficialForms\Services\NotifyNextRequiredOfficialForms;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use App\Modules\OfficialForms\Validators\OfficialFormPayloadValidator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubmitOfficialFormVersion
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization,
        private readonly OfficialFormPayloadValidator $payloadValidator = new OfficialFormPayloadValidator,
        private readonly NotifyNextRequiredOfficialForms $nextFormNotifications = new NotifyNextRequiredOfficialForms,
    ) {
        // Dependencies are injectable so submission side effects remain testable.
    }

    /** @var list<string> */
    private const ALLOWED_SUBMISSION_STATUSES = ['submitted', 'in_progress', 'pending_action'];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        User $actor,
        OfficialFormInstance $instance,
        array $payload,
        string $nextStatus = 'submitted'
    ): OfficialFormVersion {
        if (! in_array($nextStatus, self::ALLOWED_SUBMISSION_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid submission status [{$nextStatus}].");
        }

        $formCode = strtoupper($instance->definition->code);
        $validatedPayload = $this->payloadValidator->validate($formCode, $payload);

        if ($formCode === 'RES-026') {
            $topics = array_values(array_filter(
                array_map(static fn (mixed $title): string => trim((string) $title), $validatedPayload['topics'] ?? []),
                static fn (string $title): bool => $title !== ''
            ));

            if (count($topics) !== 3) {
                throw new InvalidArgumentException('RES-026 requires exactly three non-blank proposed research titles.');
            }

            foreach ($topics as $title) {
                if (mb_strlen($title) > 500) {
                    throw new InvalidArgumentException('Each proposed research title must not exceed 500 characters.');
                }
            }

            $validatedPayload['topics'] = $topics;
        }

        return DB::transaction(function () use ($actor, $instance, $nextStatus, $validatedPayload, $formCode) {
            /** @var OfficialFormInstance $lockedInstance */
            $lockedInstance = OfficialFormInstance::query()
                ->lockForUpdate()
                ->findOrFail($instance->id);

            if (in_array($lockedInstance->status, ['approved', 'completed', 'cancelled', 'superseded'], true)) {
                throw new InvalidArgumentException("Form instance #{$lockedInstance->id} is finalized and cannot accept new versions.");
            }

            if (! $this->authorization->canSubmit($actor, $lockedInstance)) {
                throw new InvalidArgumentException("User #{$actor->id} is not authorized to submit versions on form instance #{$lockedInstance->id}.");
            }

            $currentVersion = $lockedInstance->currentVersion;
            $oldStatus = $lockedInstance->status;

            if (strtoupper($lockedInstance->definition->code) === 'RES-048') {
                $validatedPayload = $this->preparePeerEvaluationPayload(
                    $actor,
                    $currentVersion?->source_snapshot,
                    $validatedPayload,
                );
            }

            $isInitialDraft = $currentVersion !== null
                && (int) $currentVersion->version_number === 1
                && $lockedInstance->status === 'draft'
                && $currentVersion->signatures()->count() === 0;

            if ($isInitialDraft) {
                $currentVersion->update([
                    'payload' => $validatedPayload,
                    'created_by' => $actor->id,
                ]);

                $lockedInstance->update(['status' => $nextStatus]);

                if ($formCode === 'RES-036' && $lockedInstance->source_type === DefenseSchedule::class) {
                    $this->syncDefenseEvaluationSubmission($actor, $lockedInstance, $validatedPayload);
                    $this->applyPanelistEvaluationSignature($actor, $lockedInstance, $currentVersion);
                }

                AuditLog::query()->create([
                    'user_id' => $actor->id,
                    'actor_name' => $actor->name,
                    'actor_email' => $actor->email,
                    'event' => strtoupper($lockedInstance->definition->code) === 'RES-026' ? 'RES026_SUBMITTED' : 'official_form.submitted',
                    'auditable_type' => OfficialFormInstance::class,
                    'auditable_id' => $lockedInstance->id,
                    'description' => "Submitted official form version v1 (status: {$nextStatus}).",
                    'subject_snapshot' => [
                        'actor_function' => 'form_submitter',
                        'old_status' => $oldStatus,
                        'new_status' => $nextStatus,
                        'version_number' => 1,
                    ],
                ]);

                $this->nextFormNotifications->handle($actor, $lockedInstance);

                return $currentVersion;
            }

            if ($currentVersion !== null && $currentVersion->payload === $validatedPayload) {
                $lockedInstance->update(['status' => $nextStatus]);

                if ($formCode === 'RES-036' && $lockedInstance->source_type === DefenseSchedule::class) {
                    $this->syncDefenseEvaluationSubmission($actor, $lockedInstance, $validatedPayload);
                    $this->applyPanelistEvaluationSignature($actor, $lockedInstance, $currentVersion);
                }

                AuditLog::query()->create([
                    'user_id' => $actor->id,
                    'actor_name' => $actor->name,
                    'actor_email' => $actor->email,
                    'event' => strtoupper($lockedInstance->definition->code) === 'RES-026' ? 'RES026_SUBMITTED' : 'official_form.submitted',
                    'auditable_type' => OfficialFormInstance::class,
                    'auditable_id' => $lockedInstance->id,
                    'description' => "Submitted unchanged official form version v{$currentVersion->version_number} (status: {$nextStatus}).",
                    'subject_snapshot' => [
                        'actor_function' => 'form_submitter',
                        'old_status' => $oldStatus,
                        'new_status' => $nextStatus,
                        'version_number' => $currentVersion->version_number,
                    ],
                ]);

                $this->nextFormNotifications->handle($actor, $lockedInstance);

                return $currentVersion;
            }

            $nextVersionNumber = ($lockedInstance->versions()->max('version_number') ?? 0) + 1;

            if ($currentVersion) {
                $lockedInstance->versions()->where('id', $currentVersion->id)->update(['is_current' => false]);
            }

            $newVersion = OfficialFormVersion::query()->create([
                'official_form_instance_id' => $lockedInstance->id,
                'version_number' => $nextVersionNumber,
                'payload' => $validatedPayload,
                'source_snapshot' => $currentVersion?->source_snapshot,
                'created_by' => $actor->id,
                'supersedes_version_id' => $currentVersion?->id,
                'is_current' => true,
            ]);

            $lockedInstance->update([
                'current_version_id' => $newVersion->id,
                'status' => $nextStatus,
            ]);

            if ($formCode === 'RES-036' && $lockedInstance->source_type === DefenseSchedule::class) {
                $this->syncDefenseEvaluationSubmission($actor, $lockedInstance, $validatedPayload);
                $this->applyPanelistEvaluationSignature($actor, $lockedInstance, $newVersion);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => strtoupper($lockedInstance->definition->code) === 'RES-026' ? 'RES026_SUBMITTED' : 'official_form.version_submitted',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $lockedInstance->id,
                'description' => "Submitted official form version v{$nextVersionNumber} (status: {$nextStatus}).",
                'subject_snapshot' => [
                    'actor_function' => 'form_submitter',
                    'old_status' => $oldStatus,
                    'new_status' => $nextStatus,
                    'version_number' => $nextVersionNumber,
                ],
            ]);

            $this->nextFormNotifications->handle($actor, $lockedInstance);

            return $newVersion;
        });
    }

    private function syncDefenseEvaluationSubmission(User $actor, OfficialFormInstance $instance, array $payload): void
    {
        $scheduleId = (int) $instance->source_id;
        /** @var DefenseEvaluationRound|null $round */
        $round = DefenseEvaluationRound::query()
            ->with(['roundPanelists', 'roundStudents', 'evaluations.studentScores'])
            ->where('defense_schedule_id', $scheduleId)
            ->whereIn('status', ['open', 'in_progress'])
            ->latest('id')
            ->first();

        if (! $round) {
            return;
        }

        $roundPanelist = $round->roundPanelists->firstWhere('panelist_user_id', $actor->id);
        if (! $roundPanelist) {
            return;
        }

        $rubric = app(Res036Rubric::class);
        $detailedPaper = isset($payload['res_036_paper_scores'])
            ? $rubric->validatePaperScores($payload['res_036_paper_scores'])
            : null;
        $paperRatings = $payload['res_036_paper_ratings'] ?? [];
        $q = $detailedPaper ? null : (isset($paperRatings[0]) && is_numeric($paperRatings[0]) ? (float) $paperRatings[0] : 0.0);
        $o = $detailedPaper ? null : (isset($paperRatings[1]) && is_numeric($paperRatings[1]) ? (float) $paperRatings[1] : 0.0);
        $r = $detailedPaper ? null : (isset($paperRatings[2]) && is_numeric($paperRatings[2]) ? (float) $paperRatings[2] : 0.0);
        $paperTotal = $detailedPaper['total'] ?? round($q + $o + $r, 2);

        $comments = $payload['res_036_paper_comments'] ?? [];
        $generalComments = is_array($comments) ? implode("\n\n", array_filter(array_map('trim', $comments))) : (string) $comments;

        $evaluation = DefenseEvaluation::query()->updateOrCreate(
            [
                'defense_evaluation_round_id' => $round->id,
                'panelist_user_id' => $actor->id,
            ],
            [
                'round_panelist_id' => $roundPanelist->id,
                'status' => 'submitted',
                'research_quality_score' => $q,
                'originality_score' => $o,
                'relevance_score' => $r,
                'paper_criterion_scores' => $detailedPaper['scores'] ?? null,
                'rubric_version' => $detailedPaper ? Res036Rubric::VERSION : null,
                'research_paper_total' => $paperTotal,
                'general_comments' => $generalComments,
                'submitted_at' => now(),
            ]
        );

        $instance->update(['defense_evaluation_id' => $evaluation->id]);

        $presenters = $payload['res_036_presenters'] ?? [];
        if (is_array($presenters)) {
            $studentIndex = 0;
            foreach ($round->roundStudents as $roundStudent) {
                $studentIndex++;
                $presData = $presenters[$studentIndex] ?? null;
                if ($presData) {
                    $detailedPresentation = isset($presData['scores']) ? $rubric->validatePresentationScores($presData['scores']) : null;
                    if ($detailedPresentation) {
                        $scores = $detailedPresentation['scores'];
                        $comm = round((($scores['voice_projection_pronunciation'] + $scores['grammar_sentence_structure']) / 20) * 100, 2);
                        $org = round((($scores['assigned_topic_clarity'] + $scores['participation_in_defense']) / 30) * 100, 2);
                        $eff = round((($scores['ability_to_answer_questions'] + $scores['mastery_of_study_details'] + $scores['ability_to_convince_panelists']) / 50) * 100, 2);
                        $total = $detailedPresentation['total'];
                    } else {
                        $comm = isset($presData['communication']) && is_numeric($presData['communication']) ? (float) $presData['communication'] : 0.0;
                        $org = isset($presData['organization']) && is_numeric($presData['organization']) ? (float) $presData['organization'] : 0.0;
                        $eff = isset($presData['effectiveness']) && is_numeric($presData['effectiveness']) ? (float) $presData['effectiveness'] : 0.0;
                        $total = round($comm + $org + $eff, 2);
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
                            'presentation_criterion_scores' => $detailedPresentation['scores'] ?? null,
                            'presentation_total' => $total,
                        ]
                    );
                }
            }
        }

        $submittedCount = DefenseEvaluation::query()
            ->where('defense_evaluation_round_id', $round->id)
            ->where('status', 'submitted')
            ->count();

        $totalRequired = $round->roundPanelists->count();
        if ($totalRequired > 0 && $submittedCount >= $totalRequired) {
            $round->update([
                'status' => 'complete',
                'all_submitted_at' => now(),
            ]);
            app(SubmitDefenseEvaluation::class)->generateSummaryAndRes037($round);
        } elseif ($round->status === 'open') {
            $round->update(['status' => 'in_progress']);
        }
    }

    /**
     * @param  array<string, mixed>|null  $sourceSnapshot
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function preparePeerEvaluationPayload(User $actor, ?array $sourceSnapshot, array $payload): array
    {
        if ((int) ($sourceSnapshot['evaluator_user_id'] ?? 0) !== (int) $actor->id) {
            throw new InvalidArgumentException('RES-048 evaluator identity does not match its frozen roster.');
        }

        $roster = $sourceSnapshot['roster'] ?? null;
        if (! is_array($roster) || count($roster) < 1 || count($roster) > 4) {
            throw new InvalidArgumentException('RES-048 has no valid frozen group roster.');
        }

        if (! in_array($payload['evaluation_phase'] ?? null, ['proposal', 'final'], true)) {
            throw new InvalidArgumentException('RES-048 requires a proposal or final evaluation phase.');
        }

        if (($payload['evaluation_date'] ?? '') === '') {
            throw new InvalidArgumentException('RES-048 requires an evaluation date.');
        }

        $rows = array_values($payload['ratings'] ?? []);
        if (count($rows) !== 10) {
            throw new InvalidArgumentException('RES-048 requires ratings for all ten criteria.');
        }

        $columnCount = count($roster);
        $normalizedRows = [];
        $totals = array_fill(0, $columnCount, 0);

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('RES-048 contains a malformed rating row.');
            }

            $values = array_values($row);
            if (count($values) !== $columnCount) {
                throw new InvalidArgumentException('RES-048 rating columns must exactly match the frozen group roster.');
            }

            $normalizedRow = [];
            foreach ($values as $column => $rating) {
                if (! is_numeric($rating) || (int) $rating < 1 || (int) $rating > 4 || (string) (int) $rating !== trim((string) $rating)) {
                    throw new InvalidArgumentException('RES-048 ratings must be whole numbers between 1 and 4.');
                }

                $normalizedRow[] = (int) $rating;
                $totals[$column] += (int) $rating;
            }

            $normalizedRows[] = $normalizedRow;
        }

        return [
            'evaluation_phase' => $payload['evaluation_phase'],
            'ratings' => $normalizedRows,
            'evaluation_date' => $payload['evaluation_date'],
            'totals' => $totals,
        ];
    }

    private function applyPanelistEvaluationSignature(User $actor, OfficialFormInstance $instance, OfficialFormVersion $version): void
    {
        if (UserSignature::query()->where('user_id', $actor->id)->exists()) {
            try {
                app(ApplyOfficialFormSignature::class)->handle($actor, $instance->id, $version->id, 'evaluate');
            } catch (\Throwable) {
                // If already signed or cannot apply, fail-soft without breaking submission
            }
        }
    }
}
