<?php

namespace App\Services\BEST;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TokenService
{
    public function getToken()
    {
        return Cache::remember('best_api_token', 3000, function () {
            $response = Http::withOptions([
                'verify' => (bool) config('services.best.verify_ssl', true),
            ])->asForm()->post(config('services.best.token_url'), [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.best.client_id'),
                'client_secret' => config('services.best.client_secret'),
            ]);

            // Never log client secrets or access tokens.
            Log::info('BEST token response', [
                'token_url' => config('services.best.token_url'),
                'grant_type' => 'client_credentials',
                'client_id' => substr((string) config('services.best.client_id'), 0, 6).'…',
                'status' => $response->status(),
                'ok' => $response->successful(),
            ]);



            return $response->json()['access_token'] ?? null;
        });
    }
}