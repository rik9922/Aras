<?php

namespace App\Http\Middleware;

use App\Jobs\CreateQuickBooksCustomer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureQuickBooksCustomer
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->role_id == 3 && ! $user->clientProfile?->quickbooks_customer_id) {
            CreateQuickBooksCustomer::dispatch($user->id, [
                'name'         => $user->name,
                'company_name' => $user->name,
                'email'        => $user->email,
                'phone'        => $user->phone,
                'address'      => $user->clientProfile?->address ?? $user->address,
            ]);
        }

        return $next($request);
    }
}