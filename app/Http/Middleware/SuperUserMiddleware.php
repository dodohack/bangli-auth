<?php
/**
 * Route middleware for super user
 */
namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class SuperUserMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request, check if the request is sent by super user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string $role
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            return response('Unauthorized.', 401);
        }

        try {
            $user = $this->auth->authenticate();
        } catch (TokenExpiredException $e) {
            return response('Token Expired', 401);
        } catch (JWTException $e) {
            return response('Token Invalid', 401);
        }

        if (!$user) {
            return response('Unauthorized', 401);
        }

        if (!$user->superUser) {
            return response('Unauthorized', 401);
        }
        
        return $next($request);
    }
}
