<?php

namespace Tests\Unit;

use App\Modules\Evaluations\Services\Res036Rubric;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class Res036RubricTest extends TestCase
{
    public function test_official_paper_and_presentation_criteria_total_one_hundred(): void
    {
        $rubric = new Res036Rubric;

        $paper = $rubric->validatePaperScores(Res036Rubric::PAPER_MAXIMUMS);
        $presentation = $rubric->validatePresentationScores(Res036Rubric::PRESENTATION_MAXIMUMS);

        self::assertSame(100.0, $paper['total']);
        self::assertSame(100.0, $presentation['total']);
    }

    public function test_score_cannot_exceed_its_specific_criterion_maximum(): void
    {
        $scores = Res036Rubric::PAPER_MAXIMUMS;
        $scores['literature_review_background'] = 16;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('between 0 and 15');

        (new Res036Rubric)->validatePaperScores($scores);
    }

    public function test_all_official_criteria_are_required_for_submission(): void
    {
        $scores = Res036Rubric::PRESENTATION_MAXIMUMS;
        unset($scores['ability_to_answer_questions']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required student presentation score');

        (new Res036Rubric)->validatePresentationScores($scores);
    }
}
