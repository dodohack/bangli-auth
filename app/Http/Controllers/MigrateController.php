<?php
/**
 * Migrate wordpress user to current system.
 * Only API server can access this, JWT_SECRET should be sent by API server
 * to authenticate.
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Tymon\JWTAuth\JWTAuth;
use App\User;
use App\SuperUser;
use App\Domain;
use App\UserDomain;

class MigrateController extends Controller
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
     * Migrate wordpress user to current auth server
     * NOTE: User password is already hashed and has different hash algorithm
     * API server will send a request to this end point when it is going to
     * migrate each user.
     * @param Request $request http request
     * @return Response $response  json data contains uuid, email and name
     */
    public function postMigrateUser(Request $request)
    {
        if (strcmp(config('auth.secret'), $request->input('secret'))) {
            return config('status.1');
        }

        $this->validateMigrationReq($request);

        if ($this->isDuplicateUser($request)) {
            /* Return the user with duplicated status, as some users may be
             * duplicated when they comes from different domain. */
            $u = User::query()->where('email', $request->input('email'))
                ->orWhere('name', $request->input('name'))
                ->first();

            /* For duplicated user, user access domain and super user
             * permission must be migrated */
            $this->migrateSuperUser($request, $u);
            $this->migrateUserDomains($request, $u);

            return array_merge(config('status.2'), [
                'uuid' => $u->getJWTIdentifier(),
                'name' => $u->name,
                'display_name' => $u->display_name,
                'email' => $u->email
            ]);
        }

        $user = new User;
        $user->name = $request->input('name');
        $user->display_name = $request->input('display_name');
        $user->email = $request->input('email');
        /* Wordpress hashed password */
        $user->password = $request->input('password');
        $user->is_wp_pwd = true;

        /* Try to save user to database */
        if (!$user->save())
            return response(500, "Failed to store the user");

        // Set the super user relationship
        $this->migrateSuperUser($request, $user);

        /* Add user as domain user domains */
        $this->migrateUserDomains($request, $user);

        /* All good, return status and data to API server */
        return array_merge(config('status.0'), [
            'uuid' => $user->getJWTIdentifier(),
            'display_name' => $user->display_name,
            'name' => $user->name,
            'email' => $user->email
        ]);
    }

    private function validateMigrationReq(Request $request)
    {
        /* Only do sanity check */
        $this->validate($request, [
            'domain'  => 'required',
            'role' => 'required|max:255',
            'name' => 'required|max:255',
            /* Some invalid email address exists in old database */
            'email' => 'required|max:255',
            'password' => 'required|min:6'
        ]);
    }

    /**
     * Check if we have duplicate entry of unique name and email
     *
     * @param Request $request
     * @return bool
     */
    private function isDuplicateUser(Request $request)
    {
        $num = User::query()->where('email', $request->input('email'))
            ->orWhere('name', $request->input('name'))
            ->get()->count();

        if ($num)
            return true;

        return false;
    }

    /**
     * Migrate super user permission for given user
     * @param Request $request
     */
    private function migrateSuperUser(Request $request, $user) {
        if ($request->input('role') === 'administrator') {
            $su = new SuperUser;
            $count = $su->where('uuid', $user->uuid)->count();
            if (!$count) {
                $su->uuid = $user->uuid;
                $su->save();
            }
        }
    }

    /**
     * Create user domain relationship
     * @param Request $request
     * @param $user
     */
    private function migrateUserDomains(Request $request, $user)
    {
        // API server passes in root domain with each request, such as
        // bangli.uk, huluwa.uk, we need to convert it to domain key such as
        // bangli_uk, huluwa_uk.
        $domainKey = str_replace('.', '_', $request->input('domain'));
        $domainId  = Domain::where('key', $domainKey)->first()->id;

        $isDashboardUser = false;
        switch ($request->input('role'))
        {
            case 'administrator':
            case 'shop_manager':
            case 'editor':
            case 'author':
                $isDashboardUser = true;
                break;
        }

        $ud = new UserDomain;
        $count = $ud->where('uuid', $user->uuid)
            ->where('domain_id', $domainId)
            ->where('dashboard_user', $isDashboardUser)->count();

        // Attach the user to the domain with dashboard user permission if any
        if (!$count) {
            $user->domains()
                ->attach($domainId, ['dashboard_user' => $isDashboardUser]);
        }
    }

}
