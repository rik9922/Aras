<?php

namespace App\Helper;

use Illuminate\Support\Facades\Cache;

class QuickBooksHelper
{
    public static function isConnected(): bool
    {
        $accessToken = Cache::get('qb_access_token');
        $refreshToken = Cache::get('qb_refresh_token');
        $realmId = Cache::get('qb_realm_id');
        $refreshTokenExpiresAt = Cache::get('qb_refresh_token_expires_at');

        if (!$accessToken || !$refreshToken || !$realmId) {
            return false;
        }

        if ($refreshTokenExpiresAt && now()->greaterThan($refreshTokenExpiresAt)) {
            return false;
        }

        return true;
    }
}
