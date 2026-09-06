<?php

namespace App\Http\Controllers\Panelist;

use App\Http\Controllers\Controller;
use App\Models\DefenseEvaluationRound;
use App\Modules\Evaluations\Actions\SaveDefenseEvaluationDraft;
use App\Modules\Evaluations\Actions\SubmitDefenseEvaluation;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function show(Request $request, DefenseEvaluationRound $round, GetEvaluationRoundData $query): JsonResponse
    {
        $data = $query->forPanelist($request->user(), $round->defense);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function saveDraft(Request $request, DefenseEvaluationRound $round, SaveDefenseEvaluationDraft $action): JsonResponse
    {
        $data = $request->validate([
            'research_quality_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'originality_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'relevance_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paper_scores' => ['nullable', 'array'],
            'paper_scores.*' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'general_comments' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'student_scores' => ['nullable', 'array'],
            'student_scores.*.communication_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'student_scores.*.organization_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'student_scores.*.effectiveness_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'student_scores.*.presentation_scores' => ['nullable', 'array'],
            'student_scores.*.presentation_scores.*' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ]);

        $evaluation = $action->handle($request->user(), $round, $data);

        return response()->json([
            'status' => 'success',
            'message' => 'Defense evaluation draft saved successfully.',
            'evaluation' => $evaluation,
        ]);
    }

    public function submit(Request $request, DefenseEvaluationRound $round, SubmitDefenseEvaluation $action): JsonResponse
    {
        $data = $request->validate([
            'research_quality_score' => ['nullable', 'required_without:paper_scores', 'numeric', 'min:0', 'max:100'],
            'originality_score' => ['nullable', 'required_without:paper_scores', 'numeric', 'min:0', 'max:100'],
            'relevance_score' => ['nullable', 'required_without:paper_scores', 'numeric', 'min:0', 'max:100'],
            'paper_scores' => ['nullable', 'required_without:research_quality_score', 'array'],
            'paper_scores.*' => ['required', 'numeric', 'min:0', 'max:20'],
            'general_comments' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'student_scores' => ['required', 'array'],
            'student_scores.*.communication_score' => ['nullable', 'required_without:student_scores.*.presentation_scores', 'numeric', 'min:0', 'max:100'],
            'student_scores.*.organization_score' => ['nullable', 'required_without:student_scores.*.presentation_scores', 'numeric', 'min:0', 'max:100'],
            'student_scores.*.effectiveness_score' => ['nullable', 'required_without:student_scores.*.presentation_scores', 'numeric', 'min:0', 'max:100'],
            'student_scores.*.presentation_scores' => ['nullable', 'array'],
            'student_scores.*.presentation_scores.*' => ['required', 'numeric', 'min:0', 'max:20'],
        ]);

        $evaluation = $action->handle($request->user(), $round, $data);

        return response()->json([
            'status' => 'success',
            'message' => 'Defense evaluation submitted successfully.',
            'evaluation' => $evaluation,
        ]);
    }
}
