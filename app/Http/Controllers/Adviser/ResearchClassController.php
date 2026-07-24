<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classes\CreateResearchClassRequest;
use App\Modules\Classes\Actions\CreateResearchClass;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ResearchClassController extends Controller
{
    public function store(
        CreateResearchClassRequest $request,
        CreateResearchClass $createResearchClass,
    ): JsonResponse|RedirectResponse {
        try {
            $researchClass = $createResearchClass->handle(
                $request->user(),
                $request->string('creation_token')->toString(),
                $request->string('name')->toString(),
                $request->input('description'),
                $request->input('join_code'),
                $request->integer('max_students'),
            );
        } catch (DuplicateClassOperation $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 409);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Class created successfully.',
                'class' => [
                    'id' => $researchClass->getKey(),
                    'name' => $researchClass->name,
                    'join_code' => $researchClass->revealJoinCode(),
                    'max_students' => $researchClass->max_students,
                    'is_active' => $researchClass->is_active,
                ],
            ], 201);
        }

        return to_route('adviser.dashboard', ['tab' => 'classes'])
            ->with('class_success', 'Class created successfully. Share its join code only with your students.');
    }

    private function errorResponse(
        CreateResearchClassRequest $request,
        string $message,
        int $status,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('adviser.dashboard', ['tab' => 'classes'])
            ->withErrors(['class' => $message]);
    }
}
