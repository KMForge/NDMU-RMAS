<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Dashboard\Services\UserLiveStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLiveStateController extends Controller
{
    public function show(Request $request, UserLiveStateService $liveStateService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($liveStateService->compute($user));
    }
}
