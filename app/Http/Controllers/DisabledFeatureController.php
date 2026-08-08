<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DisabledFeatureController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $message = 'This backend feature is disabled in the rebuild baseline.';

        if ($request->expectsJson()) {
            return response([
                'message' => $message,
            ], 410);
        }

        if ($request->isMethod('GET')) {
            abort(410, $message);
        }

        return back()->withErrors([
            'feature' => $message,
        ]);
    }
}
