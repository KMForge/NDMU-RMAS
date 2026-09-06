<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Modules\DefenseScheduling\Services\DefenseEndorsementEligibility;
use App\Modules\Evaluations\Actions\CompleteDefenseAfterEvaluation;
use App\Modules\Evaluations\Actions\DesignateEvaluationSummarySigner;
use App\Modules\Evaluations\Actions\OpenDefenseEvaluationRound;
use App\Modules\Evaluations\Actions\ReleaseDefenseEvaluationResults;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EvaluationRoundController extends Controller
{
    public function open(
        Request $request,
        Defense $defense,
        OpenDefenseEvaluationRound $action,
        DefenseEndorsementEligibility $endorsementEligibility,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'designated_signer_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        try {
            $defense->loadMissing('group');
            if ($defense->group === null) {
                throw new \InvalidArgumentException('The defense is not linked to a research group.');
            }

            $endorsementEligibility->ensureComplete($defense->group, $defense->defense_type);
            $round = $action->handle($request->user(), $defense, $validated['designated_signer_user_id'] ?? null);
        } catch (\InvalidArgumentException|AuthorizationException $e) {
            if (! $request->expectsJson()) {
                return back()->withErrors(['defense_schedule' => $e->getMessage()]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        if (! $request->expectsJson()) {
            return back()->with('status', 'Defense evaluation round opened successfully. Panelists may now score this defense.');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Defense evaluation round opened successfully.',
            'round' => $round,
        ], 201);
    }

    public function designateSigner(Request $request, DefenseEvaluationRound $round, DesignateEvaluationSummarySigner $action): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'summary_signer_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $updatedRound = $action->handle($request->user(), $round, (int) $validated['summary_signer_user_id']);
        } catch (\InvalidArgumentException|AuthorizationException $e) {
            if (! $request->expectsJson()) {
                return back()->withErrors(['defense_schedule' => $e->getMessage()]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        if (! $request->expectsJson()) {
            return back()->with('status', 'Summary signer designated successfully.');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Summary signer designated successfully.',
            'round' => $updatedRound,
        ]);
    }

    public function release(Request $request, DefenseEvaluationRound $round, ReleaseDefenseEvaluationResults $action): JsonResponse|RedirectResponse
    {
        try {
            $releasedRound = $action->handle($request->user(), $round);
        } catch (\InvalidArgumentException|AuthorizationException $e) {
            if (! $request->expectsJson()) {
                return back()->withErrors(['defense_schedule' => $e->getMessage()]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        if (! $request->expectsJson()) {
            return back()->with('status', 'Defense evaluation results released successfully.');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Defense evaluation results released successfully.',
            'round' => $releasedRound,
        ]);
    }

    public function complete(
        Request $request,
        Defense $defense,
        CompleteDefenseAfterEvaluation $action,
        DefenseEndorsementEligibility $endorsementEligibility,
    ): JsonResponse|RedirectResponse {
        try {
            $defense->loadMissing('group');
            if ($defense->group === null) {
                throw new \InvalidArgumentException('The defense is not linked to a research group.');
            }

            $endorsementEligibility->ensureComplete($defense->group, $defense->defense_type);
            $completedDefense = $action->handle($request->user(), $defense);
        } catch (\InvalidArgumentException|AuthorizationException $e) {
            if (! $request->expectsJson()) {
                return back()->withErrors(['defense_schedule' => $e->getMessage()]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        if (! $request->expectsJson()) {
            return back()->with('status', 'Defense marked as completed.');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Defense marked as completed.',
            'defense' => $completedDefense,
        ]);
    }
}
