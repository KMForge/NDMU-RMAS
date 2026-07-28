<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Http\Requests\Revisions\ManageRevisionRequest;
use App\Models\RevisionRequest;
use App\Modules\Revisions\Actions\TransitionRevisionRequest;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class RevisionRequestController extends Controller
{
    public function resolve(
        ManageRevisionRequest $request,
        RevisionRequest $revisionRequest,
        TransitionRevisionRequest $transition,
    ): JsonResponse|RedirectResponse {
        try {
            $revisionRequest = $transition->resolve(
                $request->user(),
                $revisionRequest,
                $request->validated('notes'),
                $request->ip(),
            );
        } catch (RevisionWorkflowException $exception) {
            return $this->errorResponse($request, $exception->getMessage());
        }

        return $this->successResponse(
            $request,
            $revisionRequest,
            'Revision request resolved.',
        );
    }

    public function reopen(
        ManageRevisionRequest $request,
        RevisionRequest $revisionRequest,
        TransitionRevisionRequest $transition,
    ): JsonResponse|RedirectResponse {
        try {
            $revisionRequest = $transition->reopen(
                $request->user(),
                $revisionRequest,
                $request->validated('notes'),
                $request->ip(),
            );
        } catch (RevisionWorkflowException $exception) {
            return $this->errorResponse($request, $exception->getMessage());
        }

        return $this->successResponse(
            $request,
            $revisionRequest,
            'Revision request reopened.',
        );
    }

    private function successResponse(
        ManageRevisionRequest $request,
        RevisionRequest $revisionRequest,
        string $message,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'revision' => [
                    'id' => $revisionRequest->getKey(),
                    'status' => $revisionRequest->status->value,
                    'resolved_at' => $revisionRequest->resolved_at?->toIso8601String(),
                ],
            ]);
        }

        return to_route('adviser.dashboard', ['tab' => 'revisions'])
            ->with('revision_success', $message);
    }

    private function errorResponse(
        ManageRevisionRequest $request,
        string $message,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 409);
        }

        return to_route('adviser.dashboard', ['tab' => 'revisions'])
            ->withErrors(['revision' => $message]);
    }
}
