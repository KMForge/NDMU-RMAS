<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\OfficialFormInstance;
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

            return to_route('adviser.dashboard', ['tab' => 'classes'])
                ->withErrors(['adviser_request' => $exception->getMessage()]);
        }

        if ($validated['decision'] === 'accept') {
            $invitation = OfficialFormInstance::query()
                ->where('source_type', ResearchClassGroupAdviserRequest::class)
                ->where('source_id', $updatedRequest->getKey())
                ->whereHas('definition', fn ($query) => $query->where('code', 'RES-027'))
                ->latest('id')
                ->firstOrFail();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Review and digitally sign RES-027 to accept the adviser assignment.',
                    'adviser_request' => [
                        'id' => $updatedRequest->getKey(),
                        'status' => $updatedRequest->status,
                    ],
                    'official_form_url' => route('official-forms.workspace.show', $invitation),
                ]);
            }

            return to_route('official-forms.workspace.show', $invitation)
                ->with('official_form_success', 'Review and digitally sign RES-027 to accept the adviser assignment.');
        }

        $message = 'Adviser request declined.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'adviser_request' => [
                    'id' => $updatedRequest->getKey(),
                    'status' => $updatedRequest->status,
                ],
            ]);
        }

        return to_route('adviser.dashboard', ['tab' => 'classes'])
            ->with('adviser_success', $message);
    }
}
