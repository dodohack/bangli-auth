<?php
/**
 * Pivot table between user and user is a super user
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class SuperUser extends Model
{
    protected $table  = 'super_users';
    public  $timestamps = false;

    public function user()
    {
        return $this->belongsTo('App\User', 'uuid');
    }
}
