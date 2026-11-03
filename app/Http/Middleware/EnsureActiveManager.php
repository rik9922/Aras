<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && (int) $user->role_id === 6 && (string) $user->status !== '1') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Your account has been deactivated.',
                'error_type' => 'inactive_account',
                'data' => new \stdClass(),
            ], 403);
        }

        return $next($request);
    }
}
