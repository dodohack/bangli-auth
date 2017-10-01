<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

use App\User;
use App\SuperUser;
use App\Domain;
use App\UserDomain;

class UserController extends Controller
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
     * Return user profiles includes domain, dashboard etc
     */
    public function getUser(Request $request, $uuid)
    {
        if (!$this->isValidUuid($uuid))
            return response('Invalid UUID', 401);

        // TODO: We only do this for request comes from dashboard
        // Don't do this for frontend route which may leak info to public.
        // 1. Get all available domains with the user
        $domains = Domain::get()->toArray();

        // 2. Get user with domains and dashboard permissions
        $user = User::where('uuid', $uuid)
            ->with(['domains', 'superUser'])->first()->toArray();

        // Flatten pivot table
        $newDomains = array();
        foreach ($user['domains'] as $domain) {
            $domain['dashboard_user'] = $domain['pivot']['dashboard_user'];
            unset($domain['pivot']);
            array_push($newDomains, $domain);
        }
        $user['domains'] = $newDomains;

        $json = compact('domains', 'user');
        /* Return JSONP or AJAX data */
        return parent::success($request, $json);
    }

    /**
     * Update a user auth profile.
     * THIS FUNCTION MUST BE ONLY ACCESSABLE BY SUPER USER.
     */
    public function putUser(Request $request, $uuid)
    {
        if (!$this->isValidUuid($uuid))
            return response("Invalid UUID", 401);

        // Update user domains if domains is specified in the request
        $this->putUserDomains($uuid, $request);

        // Update user super user permission if any
        $this->putSuperUser($uuid, $request);

        // Update user password if any
        $this->putUserPassword($uuid, $request);

        return $this->getUser($request, $uuid);
    }

    public function putUserDomains($uuid, $request)
    {
        // domains in format [id => is_dashboard_user, ...]
        $domains = $request->input('domains');
        foreach ($domains as $id => $duser) {
            $res = UserDomain::firstOrNew(['uuid' => $uuid, 'domain_id' => $id]);
            $res->dashboard_user = $duser ? 1 : 0;
            $res->save();
        }
    }

    public function putSuperUser($uuid, $request)
    {
        $inputSu = $request->input('super_user');

        /*
        TODO: Only work in lumen 5.3
        $user = User::find($uuid);

        if ($inputSu === 1) {
            $user->superUser()->associate();
            $user->save();
        } else if ($inputSu === 0) {
            $user->superUser()->dissociate();
            $user->save();
        }
        */


        if ($inputSu === 0) {
            SuperUser::where('uuid', $uuid)->delete();
        } else if ($inputSu === 1) {
            SuperUser::where('uuid', $uuid)->firstOrCreate(['uuid' => $uuid]);
        }
    }

    public function putUserPassword($uuid, $request)
    {
        // Do not update user password if these 2 fields are not given or
        // mismatch
        $pwd1 = $request->input('password');
        $pwd2 = $request->input('password_repeat');
        if (!$pwd1 || !$pwd2 || $pwd1 != $pwd2) return;

        $user = User::find($uuid);
        $user->password = app('hash')->make($pwd1);
        $user->save();
    }

    public function deleteUser(Request $request, $uuid)
    {
        // We only do soft delete user from auth server, so we can recovery
        // if user still exists on some application server.
    }

    /**
     * Add a user to domains
     */
    public function postUserToDomains(Request $request, $uuid)
    {
        if (!$this->isValidUuid($uuid))
            return response('Invalid UUID', 401);

        $domains = json_decode($request->getContent());

        foreach($domains as $domain) {
            $id = Domain::where('key', $domain->key)->first()->id;
            UserDomain::create(['user_uuid' => $uuid, 'domain_id' => $id]);
        }

        /* Return the request data on success */
        return parent::success($request, $domains);
    }

    public function deleteUserFromDomains(Request $request, $uuid)
    {
        if (!$this->isValidUuid($uuid))
            return response('Invalid UUID', 401);

        $domains = json_decode($request->getContent());

        foreach($domains as $domain) {
            $id = Domain::where('key', $domain->key)->first()->id;
            UserDomain::where('user_uuid', $uuid)
                ->where('domain_id', $id)->delete();
        }

        /* Return the domains deleted */
        return parent::success($request, $domains);
    }


    /*************************************************************************
     * Helper functions
     *************************************************************************/

    /**
     * Validate given uuid
     */
    private function isValidUuid($uuid)
    {
        if (empty($uuid)) return false;

        if (strlen($uuid) !== 32) return false;

        return true;
    }
}