<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function (): void {
    Route::get('/status', fn () => response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
    ]))->name('status');
});
