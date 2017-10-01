<?php

namespace App\Providers;

use Tymon\JWTAuth\Providers\AbstractServiceProvider;
use Tymon\JWTAuth\Http\Middleware\Check;
use Tymon\JWTAuth\Http\Parser\AuthHeaders;
use Tymon\JWTAuth\Http\Parser\QueryString;
use Tymon\JWTAuth\Http\Parser\InputSource;
use Tymon\JWTAuth\Http\Parser\LumenRouteParams;
use Tymon\JWTAuth\Http\Middleware\Authenticate;
use Tymon\JWTAuth\Http\Middleware\RefreshToken;
use Tymon\JWTAuth\Http\Middleware\AuthenticateAndRenew;
use App\BlJwtGuard;

/*
 * NOTE: This class is almost identical to
 * Tymon\JWTAuth\Providers\LumenServiceProvider.php, except we have overwrite
 * the Authentication Guard to use BlJwtGuard instead of JwtGuard.
 */
class AuthServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->app->configure('jwt');

        $path = realpath(__DIR__.'/../../vendor/tymon/jwt-auth/config/config.php');
        $this->mergeConfigFrom($path, 'jwt');

        $this->app->routeMiddleware([
            'jwt.auth' => Authenticate::class,
            'jwt.refresh' => RefreshToken::class,
            'jwt.renew' => AuthenticateAndRenew::class,
            'jwt.check' => Check::class,
        ]);

        $this->app['auth']->extend('jwt', function ($app, $name, array $config) {
            $guard = new BlJwtGuard(
                $app['tymon.jwt'],
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });

        $this->app['tymon.jwt.parser']->setChain([
            new AuthHeaders,
            new QueryString,
            new InputSource,
            new LumenRouteParams,
        ]);
    }
}
