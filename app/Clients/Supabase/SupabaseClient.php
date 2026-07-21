<?php

namespace App\Clients\Supabase;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupabaseClient
{
    public function request(bool $useServiceRole = true): PendingRequest
    {
        $url = rtrim((string) config('supabase.url'), '/');
        $key = (string) config($useServiceRole ? 'supabase.service_role_key' : 'supabase.anon_key');

        if ($url === '' || $key === '') {
            throw new RuntimeException('Supabase server credentials are not configured.');
        }

        return Http::baseUrl($url.'/storage/v1')
            ->acceptJson()
            ->withHeaders(['apikey' => $key])
            ->withToken($key)
            ->timeout((int) config('supabase.timeout', 30));
    }
}
