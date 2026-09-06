<?php

namespace App\Modules\Evaluations\Services;

use App\Models\ResearchClassGroup;
use InvalidArgumentException;

class Res036Rubric
{
    public const VERSION = 'ndmu-res036-2026';

    /** @var array<string, int> */
    public const PAPER_MAXIMUMS = [
        'relevance_of_topic' => 20,
        'literature_review_background' => 15,
        'problem_definition_objectives' => 15,
        'technical_depth_innovation' => 20,
        'methodology_feasibility' => 20,
        'expected_outcomes_contributions' => 10,
    ];

    /** @var array<string, int> */
    public const PRESENTATION_MAXIMUMS = [
        'voice_projection_pronunciation' => 10,
        'grammar_sentence_structure' => 10,
        'assigned_topic_clarity' => 15,
        'participation_in_defense' => 15,
        'ability_to_answer_questions' => 20,
        'mastery_of_study_details' => 15,
        'ability_to_convince_panelists' => 15,
    ];

    /** @return array{scores: array<string, float>, total: float} */
    public function validatePaperScores(array $scores, bool $required = true): array
    {
        return $this->validateScores($scores, self::PAPER_MAXIMUMS, 'research paper', $required);
    }

    /** @return array{scores: array<string, float>, total: float} */
    public function validatePresentationScores(array $scores, bool $required = true): array
    {
        return $this->validateScores($scores, self::PRESENTATION_MAXIMUMS, 'student presentation', $required);
    }

    public function resolveProgramCode(?ResearchClassGroup $group): ?string
    {
        if ($group === null) {
            return null;
        }

        $group->loadMissing('members.student.studentProfile.program');
        $codes = $group->members
            ->map(function ($member): ?string {
                $code = strtoupper(trim((string) $member->student?->studentProfile?->program?->code));
                if (in_array($code, ['BSCS', 'CS'], true)) {
                    return 'BSCS';
                }
                if (in_array($code, ['BSIT', 'IT'], true)) {
                    return 'BSIT';
                }

                $legacy = strtoupper((string) $member->student?->program);
                if (str_contains($legacy, 'BSCS') || str_contains($legacy, 'COMPUTER SCIENCE')) {
                    return 'BSCS';
                }
                if (str_contains($legacy, 'BSIT') || str_contains($legacy, 'INFORMATION TECHNOLOGY')) {
                    return 'BSIT';
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values();

        return $codes->count() === 1 ? $codes->first() : null;
    }

    /** @param array<string, int> $maximums
     * @return array{scores: array<string, float>, total: float}
     */
    private function validateScores(array $scores, array $maximums, string $section, bool $required): array
    {
        $unknown = array_diff(array_keys($scores), array_keys($maximums));
        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown '.$section.' criteria ['.implode(', ', $unknown).'].');
        }

        $validated = [];
        foreach ($maximums as $criterion => $maximum) {
            $value = $scores[$criterion] ?? null;
            if ($value === null || $value === '') {
                if ($required) {
                    throw new InvalidArgumentException("Missing required {$section} score for {$criterion}.");
                }

                continue;
            }
            if (! is_numeric($value) || (float) $value < 0 || (float) $value > $maximum) {
                throw new InvalidArgumentException("Score for {$criterion} must be between 0 and {$maximum}.");
            }
            $validated[$criterion] = round((float) $value, 2);
        }

        return ['scores' => $validated, 'total' => round(array_sum($validated), 2)];
    }
}
