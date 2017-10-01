<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Ramsey\Uuid\Uuid;

class User extends Model
    implements JWTSubject, AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * Use binary(16) uuid as primary key
     */
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'email_validated', 'super_user'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        /* Initialize uuid */
        $this->uuid = $this->generateUuid();
    }

    /**
     * Relationship between user and website
     */
    public function domains()
    {
        return $this->belongsToMany('App\Domain', 'user_domains',
            'uuid', 'domain_id')->withPivot('dashboard_user');
    }

    /**
     * Relationship between dashboard user and domain
     */
    public function managedDomains()
    {
        return $this->belongsToMany('App\Domain', 'user_domains',
            'uuid', 'domain_id')->wherePivot('dashboard_user', 1);
    }

    public function superUser()
    {
        return $this->hasOne('App\SuperUser', 'uuid');
    }

    /**
     * If the user is migrated from wordpress and has not logged once yet.
     */
    public function scopeWpUser($query, $email)
    {
        return $query->where('email', $email)
                     ->where('is_wp_pwd', true);
    }

    /**
     * Use UUID as JWT Subject(sub in JWT payload)
     */
    public function getJWTIdentifier()
    {
        return $this->uuid;
    }

    /**
     * Customize JWT Payload
     */
    public function getJWTCustomClaims()
    {
        return ['iss' => 'bangli.uk',
            'jti' => '0',
            /* Super user */
            'spu' => $this->superUser ? 1 : 0,
            /* Display name */
            'dpn' => $this->display_name,
            'aud' => $this->name];
    }

    /**
     * Generate uuid
     * @return string 32bit uuid character
     */
    private function generateUuid()
    {
        $uuid = Uuid::uuid4()->toString();
        $uuid = substr($uuid, 0, 8) . substr($uuid, 9, 4) .
            substr($uuid, 14, 4) . substr($uuid, 19, 4) . substr($uuid, 24);

        return $uuid;
    }
}