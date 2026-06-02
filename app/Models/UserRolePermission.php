<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role_id',
        'permission_id',
        'side_menue_id',
    ];

    protected $table = 'users_role_permissions';

    public function subadmins()
    {
        return $this->belongsTo(SubAdmin::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

  public function sideMenue()
{
    return $this->belongsTo(SideMenue::class, 'side_menue_id');
}

}
