<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleGeocodingService
{
    private const MAX_OVER_QUERY_LIMIT_RETRIES = 3;

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocodeAddress(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $apiKey = googleMapsServerKey();
        if ($apiKey === '') {
            Log::warning('Google geocoding skipped: GOOGLE_MAPS_API_KEY is not configured.');

            return null;
        }

        try {
            $response = null;
            $payload = [];

            for ($attempt = 0; $attempt <= self::MAX_OVER_QUERY_LIMIT_RETRIES; $attempt++) {
                $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'key' => $apiKey,
                ]);

                $payload = $response->json();
                if (($payload['status'] ?? '') !== 'OVER_QUERY_LIMIT'
                    || $attempt === self::MAX_OVER_QUERY_LIMIT_RETRIES) {
                    break;
                }

                $delaySeconds = 2 ** $attempt;
                Log::warning('Google geocoding rate limited; retrying.', [
                    'address' => $address,
                    'attempt' => $attempt + 1,
                    'retry_delay_seconds' => $delaySeconds,
                ]);
                sleep($delaySeconds);
            }

            if (! $response->successful()) {
                Log::warning('Google geocoding HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            if (($payload['status'] ?? '') !== 'OK' || empty($payload['results'][0]['geometry']['location'])) {
                Log::warning('Google geocoding returned no results', [
                    'status' => $payload['status'] ?? null,
                    'address' => $address,
                ]);

                return null;
            }

            $location = $payload['results'][0]['geometry']['location'];

            return [
                'lat' => (float) $location['lat'],
                'lng' => (float) $location['lng'],
            ];
        } catch (\Throwable $e) {
            Log::warning('Google geocoding failed', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
