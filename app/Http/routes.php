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

$app->get('/', function () use ($app) {
    return 'Welcome to auth.bangli.uk, nothing here!';
});

/* Login a user, if user is dashboard user, domains will be returned as well */
$app->post('/login',           'AuthController@postLogin');

/* Register a user */
$app->post('/register',        'AuthController@postRegister');

/* This route is only used by api server, to get valid tokens to blacklist */
$app->post('/invalidateTokens', 'AuthController@postInvalidateTokens');


/* Logged in user routes */
$app->group(['middleware' => 'auth:api',
             'namespace'  => 'App\Http\Controllers'], function($app)
{
    /* Refresh token, expired JWT can be refreshed if still in refresh_ttl period */
    $app->post('/refresh',         'AuthController@postRefresh');
    $app->post('/update_password', 'AuthController@postUpdatePassword');

    /* Add user to a given domain and return the user base profile */
    $app->get('/login-domain/{domain}', 'AuthController@getLoginDomain');


    // This is a placeholder route for frontend request of a user
    $app->get('/fe/user/{uuid}',   'UserController@getFeUser');

    /* Get user profile, We don't have route to get list of user profiles here,
     * as we only get user global information when view his profile.
     * NOTE: This is the route for backend only.
     */
    $app->get('/user/{uuid}',      'UserController@getUser');
});

/* Super user routes */
$app->group(['middleware' => 'superuser',
             'namespace'  => 'App\Http\Controllers'], function($app)
{
    /* Update user */
    $app->put('/user/{uuid}',      'UserController@putUser');

    /* Delete user */
    $app->delete('/user/{uuid}',   'UserController@deleteUser');


    /* Add a user to domains */
    //$app->post('/domains/user/{uuid}',   'DomainController@postUserToDomains');
    /* Remove a user from domains */
    //$app->delete('/domains/user/{uuid}', 'DomainController@deleteUserFromDomains');
});


/***************************************************************************
 * Wordpress user data migration, post from API server, only valid if
 * JWT_SECRET send by API server matches.
 ***************************************************************************/
$app->post('/migrate/user', 'MigrateController@postMigrateUser');

