<?php
/**
 * Pivot table between user and domain
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDomain extends Model
{
    protected    $table = 'user_domains';
    public  $timestamps = false;
    protected $fillable = ['uuid', 'domain_id', 'dashboard_user'];
}
