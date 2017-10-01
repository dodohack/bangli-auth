<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    public $timestamps = false;

    /**
     * Relationship between user and domain where user can manage this domain
     */
    public function dashboardUsers()
    {
        return $this->belongsToMany('App\Users',  'user_domain',
            'domain_id', 'user_uuid')->wherePivot('dashboard_user', 1);
    }

}