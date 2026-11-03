<?php

namespace App\Services\Dwolla;

use Illuminate\Support\Facades\Http;

class DwollaClient
{
    protected string $baseUrl;
    protected string $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('services.dwolla.environment') === 'sandbox'
            ? 'https://api-sandbox.dwolla.com'
            : 'https://api.dwolla.com';

        $this->accessToken = $this->getAccessToken();
    }

    protected function getAccessToken(): string
    {
        $response = Http::asForm()->post(
            $this->baseUrl . '/token',
            [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.dwolla.id'),
                'client_secret' => config('services.dwolla.secret'),
            ]
        );

        return $response->json('access_token');
    }

    public function get(string $url, array $headers = []): \Illuminate\Http\Client\Response
    {
        return Http::withToken($this->accessToken)
            ->withHeaders(array_merge([
                'Accept' => 'application/vnd.dwolla.v1.hal+json',
            ], $headers))
            ->get($this->baseUrl . $url);
    }

    public function post(string $url, array $data = []): \Illuminate\Http\Client\Response
    {
        return Http::withToken($this->accessToken)
            ->withHeaders([
                'Accept' => 'application/vnd.dwolla.v1.hal+json',
                'Content-Type' => 'application/vnd.dwolla.v1.hal+json',
            ])
            ->post($this->baseUrl . $url, $data);
    }

    public function delete(string $url): \Illuminate\Http\Client\Response
    {
        return Http::withToken($this->accessToken)
            ->withHeaders([
                'Accept' => 'application/vnd.dwolla.v1.hal+json',
            ])
            ->delete($this->baseUrl . $url);
    }
}