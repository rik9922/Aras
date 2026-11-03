<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use App\Trait\FormatResponseTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JWTMiddleware
{
    use FormatResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     return $next($request);
    // }

    public function handle($request, Closure $next)
    {

        try {
            $user = JWTAuth::parseToken()->authenticate();
            if(!$user){
                return $this->errorResponse('Token is invalid', 'invalid_token', Response::HTTP_UNAUTHORIZED, new \stdClass());
            }
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return $this->errorResponse('Token is invalid', 'invalid_token', Response::HTTP_UNAUTHORIZED, new \stdClass());
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return $this->errorResponse('Token is expired', 'expired_token', Response::HTTP_UNAUTHORIZED, new \stdClass());
            }else{
                return $this->errorResponse('Token not found', 'ERROR', Response::HTTP_UNAUTHORIZED, new \stdClass());
            }
        }
        return $next($request);
    }
}
