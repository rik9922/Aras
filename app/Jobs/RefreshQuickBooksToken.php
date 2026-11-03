<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\DataService\DataService;

class RefreshQuickBooksToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    public function handle(): void
    {
        if (!Cache::has('qb_refresh_token')) {
            Log::warning('No QuickBooks refresh token available for refresh job');
            return;
        }

        try {
            $config = config('quickbooks');
            
            if (empty($config['client_id']) || empty($config['client_secret'])) {
                Log::error('QuickBooks configuration missing client_id or client_secret');
                return;
            }

            $dataService = DataService::Configure([
                'auth_mode' => 'oauth2',
                'ClientID' => $config['client_id'],
                'ClientSecret' => $config['client_secret'],
                'RedirectURI' => $config['redirect_uri'],
                'scope' => $config['scope'],
                'baseUrl' => $config['base_url'],
            ]);

            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
            $refreshToken = Cache::get('qb_refresh_token');
            
            if (empty($refreshToken)) {
                Log::error('QuickBooks refresh token is empty');
                return;
            }
            
            $refreshedAccessToken = $OAuth2LoginHelper->refreshAccessTokenWithRefreshToken($refreshToken);

            if (!$refreshedAccessToken) {
                Log::error('QuickBooks token refresh returned null');
                return;
            }

            $accessToken = $refreshedAccessToken->getAccessToken();
            $newRefreshToken = $refreshedAccessToken->getRefreshToken();
            $expiresIn = $refreshedAccessToken->getAccessTokenValidationPeriodInSeconds();

            if (empty($accessToken) || empty($newRefreshToken)) {
                Log::error('QuickBooks refreshed token missing access or refresh token');
                return;
            }

            // Store tokens with appropriate cache expiration
            Cache::put('qb_access_token', $accessToken, 3600);
            Cache::put('qb_refresh_token', $newRefreshToken, now()->addDays(100));
            Cache::put('qb_access_token_expires_at', now()->addSeconds($expiresIn), 3600);

            Log::info('QuickBooks token refreshed successfully via job', [
                'expires_in' => $expiresIn,
                'expires_at' => now()->addSeconds($expiresIn)->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('QuickBooks token refresh job failed: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to allow job retry mechanism to work
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('QuickBooks token refresh job failed permanently after retries', [
            'message' => $exception->getMessage(),
            'exception' => get_class($exception)
        ]);
    }
}