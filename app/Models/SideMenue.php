<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SideMenue extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'side_menus';
    protected $fillable = [
        'name',
    ];

    public function rolePermissions()
    {
        return $this->hasMany(UserRolePermission::class);
    }
}
