<?php

namespace App\Models;

// use Illuminate\Auth\Authenticatable;
use App\Models\Role;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class SubAdmin extends Authenticatable implements AuthenticatableContract
{
    use HasApiTokens, HasFactory;
    protected $guard = 'subadmin';
    protected $guarded = [];
    // protected $hidden = ['password', 'remember_token'];

//     public function role()
// {
//     return $this->belongsTo(Role::class, 'role_name', 'id');
// }


  public function roles()
{
    return $this->belongsToMany(Role::class, 'user_roles', 'subadmin_id', 'role_id');
}

    public function rolePermissions()
    {
        return $this->hasMany(UserRolePermission::class);
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'id', 'permission_id');
    }
}
