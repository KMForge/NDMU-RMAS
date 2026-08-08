<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Modules\Classes\Actions\RespondResearchGroupAdviserRequest;
use App\Modules\Classes\Exceptions\ClassOperationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResearchGroupAdviserRequestController extends Controller
{
    public function respond(
        Request $request,
        ResearchClassGroupAdviserRequest $adviserRequest,
        RespondResearchGroupAdviserRequest $action,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:accept,decline'],
        ]);

        try {
            $updatedRequest = $action->handle(
                $request->user(),
                $adviserRequest,
                $validated['decision'],
            );
        } catch (ClassOperationException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return to_route('adviser.dashboard', ['tab' => 'dashboard'])
                ->withErrors(['adviser_request' => $exception->getMessage()]);
        }

        $message = $validated['decision'] === 'accept'
            ? 'Adviser request accepted successfully.'
            : 'Adviser request declined.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'adviser_request' => [
                    'id' => $updatedRequest->getKey(),
                    'status' => $updatedRequest->status,
                ],
            ]);
        }

        return to_route('adviser.dashboard', ['tab' => 'dashboard'])
            ->with('adviser_success', $message);
    }
}
