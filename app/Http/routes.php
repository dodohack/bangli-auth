<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return 'Welcome to auth.bangli.uk, nothing here!';
});

/* Login a user, if user is dashboard user, domains will be returned as well */
$router->post('/login',           'AuthController@postLogin');

/* Register a user */
$router->post('/register',        'AuthController@postRegister');

/* This route is only used by api server, to get valid tokens to blacklist */
$router->post('/invalidateTokens', 'AuthController@postInvalidateTokens');


/* Logged in user routes(guarded by Middleware\Authenticate */
$router->group(['middleware' => 'api',
    'namespace'  => '\App\Http\Controllers'], function($router) {
    /* Refresh token, expired JWT can be refreshed if still in refresh_ttl period */
    $router->post('/refresh',         'AuthController@postRefresh');
    $router->post('/update_password', 'AuthController@postUpdatePassword');

    /* Add user to a given domain and return the user base profile */
    $router->get('/login-domain/{domain}', 'AuthController@getLoginDomain');


    // This is a placeholder route for frontend request of a user
    $router->get('/fe/user/{uuid}',   'UserController@getFeUser');

    /* Get user profile, We don't have route to get list of user profiles here,
     * as we only get user global information when view his profile.
     * NOTE: This is the route for backend only.
     */
    $router->get('/user/{uuid}',      'UserController@getUser');
});

/* Super user routes */
$router->group(['middleware' => 'superuser',
             'namespace'  => '\App\Http\Controllers'], function($router)
{
    /* Update user */
    $router->put('/user/{uuid}',      'UserController@putUser');

    /* Delete user */
    $router->delete('/user/{uuid}',   'UserController@deleteUser');


    /* Add a user to domains */
    //$router->post('/domains/user/{uuid}',   'DomainController@postUserToDomains');
    /* Remove a user from domains */
    //$router->delete('/domains/user/{uuid}', 'DomainController@deleteUserFromDomains');
});


/***************************************************************************
 * Wordpress user data migration, post from API server, only valid if
 * JWT_SECRET send by API server matches.
 ***************************************************************************/
$router->post('/migrate/user', 'MigrateController@postMigrateUser');

