<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next,$role): Response
    // {
    //     if (!Auth::check() || Auth::user()->role->name !== $role) {
    //         return redirect('/login')->with('error', 'Unauthorized Access!');
    //     }

    //     return $next($request);
    // }
    
    public function handle(Request $request, Closure $next, $role): Response
    {
        // Split the roles passed into the middleware (e.g., 'Administrator,Manager')
        $roles = explode(',', $role);
    
        // Check if the user is authenticated
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Please log in to access this page!');
        }

        if ((int) Auth::user()->role_id === 6 && (string) Auth::user()->status !== '1') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors(['email' => 'Your account has been deactivated.']);
        }
    
        // Check if the user's role is one of the allowed roles
        if (!in_array(Auth::user()->role->name, $roles)) {
            return redirect('/login')->with('error', 'Unauthorized Access!');
        }
    
        // Allow the request to proceed if the user has the appropriate role
        return $next($request);
    }
}
