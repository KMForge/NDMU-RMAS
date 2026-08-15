<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Modules\Evaluations\Actions\CompleteDefenseAfterEvaluation;
use App\Modules\Evaluations\Actions\DesignateEvaluationSummarySigner;
use App\Modules\Evaluations\Actions\OpenDefenseEvaluationRound;
use App\Modules\Evaluations\Actions\ReleaseDefenseEvaluationResults;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationRoundController extends Controller
{
    public function open(Request $request, Defense $defense, OpenDefenseEvaluationRound $action): JsonResponse
    {
        $validated = $request->validate([
            'designated_signer_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $round = $action->handle($request->user(), $defense, $validated['designated_signer_user_id'] ?? null);

        return response()->json([
            'status' => 'success',
            'message' => 'Defense evaluation round opened successfully.',
            'round' => $round,
        ], 201);
    }

    public function designateSigner(Request $request, DefenseEvaluationRound $round, DesignateEvaluationSummarySigner $action): JsonResponse
    {
        $validated = $request->validate([
            'summary_signer_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $updatedRound = $action->handle($request->user(), $round, (int) $validated['summary_signer_user_id']);

        return response()->json([
            'status' => 'success',
            'message' => 'Summary signer designated successfully.',
            'round' => $updatedRound,
        ]);
    }

    public function release(Request $request, DefenseEvaluationRound $round, ReleaseDefenseEvaluationResults $action): JsonResponse
    {
        $releasedRound = $action->handle($request->user(), $round);

        return response()->json([
            'status' => 'success',
            'message' => 'Defense evaluation results released successfully.',
            'round' => $releasedRound,
        ]);
    }

    public function complete(Request $request, Defense $defense, CompleteDefenseAfterEvaluation $action): JsonResponse
    {
        $completedDefense = $action->handle($request->user(), $defense);

        return response()->json([
            'status' => 'success',
            'message' => 'Defense marked as completed.',
            'defense' => $completedDefense,
        ]);
    }
}
