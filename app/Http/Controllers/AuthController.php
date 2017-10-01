<?php

/**
 * This is the authentication for backend routes.
 * DO NOT USE THIS WITH FRONTEND ROUTE.
 */

namespace App\Http\Controllers;

use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use GuzzleHttp\Client;
use App\User;
use App\Domain;
use App\PasswordHash;

class AuthController extends Controller
{
    /**
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }

    /**
     * Process user login request
     * @return object - if user is non dashboard user, return jwt token only,
     *                - otherwise, return both own domains and jwt token.
     */
    public function postLogin(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email|max:255',
            'password' => 'required'
        ]);

        /* Grab input from the request */
        $input = $request->only('email', 'password');

        try {
            /* Verify the credentials and create a token for the user */
            if (!$token = $this->jwt->attempt($input)) {

                /* Authenticate with Wordpress password hasher */
                if (!$this->authByWpHasher($input)) {
                    return response('Wrong email or password', 404);
                } else {
                    /* Try to authenticate again and no error should happen */
                    $token = $this->jwt->attempt($input);
                }
            }
        } catch (JWTException $e) {
            return response('Can get a token', 500);
        }

        $this->jwt->setToken($token);
        // Get domains that user can manage from dashboard
        $domains = $this->jwt->user()->managedDomains()->get()->toArray();
        // Return token and domains(if any)
        $json = compact('token', 'domains');

        /* Return JWT for non dashboard user */
        return parent::success($request, $json);
    }

    /**
     * Get user profile and add user to domain given
     */
    public function getLoginDomain(Request $request, $key)
    {
        // Check if domain key is currect
        $domain = Domain::where('key', $key)->first(['id']);
        if ($domain) {
            $id = $domain->id;
            // TODO: We can use syncWithoutDetaching() but the function does not exist
            $rec = $this->jwt->user()->domains()->where('domain_id', $id)->count();
            // Create user-domain relationship if it is not exists
            if (!$rec)
                $this->jwt->user()->domains()->attach($id, ['dashboard_user' => 0]);

            $email = $this->jwt->user()->email;
            return response(compact('email'), 200);
        }

        return response("INCORRECT PARAMTER", 401);
    }

    /**
     * Refresh given JWT token if it is expired
     * @param Request $request
     * @return object - The same as function postLogin() returns
     */
    public function postRefresh(Request $request)
    {
        $this->validate($request, [
            'token'    => 'required'
        ]);

        $token = $request->input('token');
        $this->jwt->setToken($token);

        try {
            $user = $this->jwt->authenticate();
        } catch (TokenExpiredException $e) {
            // Only refresh token when it is expired
            $token = $this->jwt->refresh();
        } catch (JWTException $e) {
            return response("Unauthorized", 401);
        }

        /* Get user's domains if user is a dashboard user */
        $domains = $user->domains()->get()->toArray();
        $json = compact('token', 'domains');

        /* Return JWT for non dashboard user */
        return parent::success($request, $json);
    }

    /**
     * Process user register request, and return JWT token if success
     */
    public function postRegister(Request $request)
    {
        $this->validateRegisterReq($request);

        $user           = new User;
        $user->name     = $request->input('name');
        $user->email    = $request->input('email');
        $user->password = app('hash')->make($request->input('password'));

        /* Save the user to database */
        if (!$user->save())
            return response('FAIL TO REGISTER', 500);

        /* Generate a token, assume we don't have any exception here */
        $token = $this->jwt->fromUser($user);

        /* All good, return token to user */
        return parent::success($request, compact('token'));
    }

    /**
     * Update user password, authenticate is must
     * arguments: uuid, password, token
     * @param Request $request
     * @return object
     */
    public function postUpdatePassword(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'uuid'  => 'required|min:32|max:32',
            'password' => 'required|min:6|max:32'
        ]);

        $token    = $request->input('token');
        $uuid     = $request->input('uuid');
        $password = $request->input('password');

        $this->jwt->setToken($token);
        try {
            $user = $this->jwt->authenticate();
        } catch (TokenExpiredException $e) {
            return config('status.103'); // Token expired
        } catch (JWTException $e) {
            return config('status.102'); // Token invalid
        }

        /* TODO: Merge common code */
        if ($user->uuid === $uuid) {
            
            // Change own password, mass-assignment for password is disabled.
            $user->password = Hash::make($password);
            $user->save();
            return parent::success($request, json_encode('SUCCESS'));
            
        } else if ($user->hasRole('super_user')) {
            
            // Change others password, only by super_user
            $user = User::find($uuid);
            $user->password = Hash::make($password);
            $user->save();
            return parent::success($request, json_encode('SUCCESS'));

        }
        
        return response("FAIL TO UPDATE PASSWORD", 400);
    }

    /**
     * Remove matched tokens from table 'tokens', and return these matched
     * tokens to api server
     * @param Request $request
     */
    public function postInvalidateTokens(Request $request)
    {

    }

    /**
     * Send current registered user to API server via http post
     * @param Request $request
     * @param string $token
     * @return boolean true if api server returns 200
     */
    private function notifyApiServer(Request $request, $token)
    {
        $client = new Client();
        try {
            $res = $client->request('POST', $request->input('callback'),
                [
                    'form_params' => [
                        'token' => $token,
                        'email' => $request->input('email')
                    ]
                ]);
        } catch (ServerException $e) {
            /* Return false if fails */
            //dd($token);
            return false;
        }

        /* Return true on success */
        return true;
    }

    /**
     * Validate required request sent from client
     * @param Request $request
     */
    private function validateRegisterReq(Request $request)
    {
        $this->validate($request, [
            'name'     => 'required|max:255|unique:users',
            'email'    => 'required|email|max:255|unique:users',
            /* validate password == password_confirmation */
            'password' => 'required|confirmed|min:6'
        ]);
    }

    /**
     * Validate given email and password with Wordpress password algorithm
     * @param array $input
     * @return bool true if user password is correct
     */
    private function authByWpHasher(array $input)
    {

        $wpUser = User::wpUser($input['email'])->first();
        if ($wpUser)
        {
            $wpHasher = new PasswordHash(8, TRUE);
            if ($wpHasher->CheckPassword($input['password'], $wpUser['password']))
            {
                /* Replace old wordpress password with new hashed one */
                $new_pwd = Hash::make($input['password']);
                $wpUser->password  = $new_pwd;
                $wpUser->is_wp_pwd = false;

                /* Return true on success */
                return $wpUser->save();
            } else {
                return false;
            }
        }

        return false;
    }
}
