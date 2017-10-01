<?php

namespace App;

use Tymon\JWTAuth\JWTGuard;

class BlJwtGuard extends JWTGuard
{
    /**
     * OVERWRITE PARENT METHOD: cause we are using binary UUID in database, and
     * human readable UUID in JSON Web Token.
     *
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        if ($this->jwt->getToken() && $this->jwt->check()) {
            /* Get UUID from JWT payload sub */
            $id = $this->jwt->payload()->get('sub');

            return $this->user = $this->provider->retrieveById($id);
        }
    }

}