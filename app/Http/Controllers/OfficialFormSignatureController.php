<?php

namespace App\Http\Controllers;

use App\Models\OfficialFormSignature;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficialFormSignatureController extends Controller
{
    use AuthorizesRequests;

    public function image(Request $request, OfficialFormSignature $signature): StreamedResponse
    {
        $this->authorize('view', $signature->instance);

        if (! Storage::disk($signature->signature_storage_disk)->exists($signature->signature_storage_path)) {
            abort(404, 'Applied signature image not found.');
        }

        return Storage::disk($signature->signature_storage_disk)->response(
            $signature->signature_storage_path,
            "signature_{$signature->id}.png",
            [
                'Content-Type' => 'image/png',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]
        );
    }
}
