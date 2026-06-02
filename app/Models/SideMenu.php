<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SideMenu extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function permissions()
    {
        return $this->hasMany(SubAdminPermission::class, 'side_menu_id', 'id');
    }
}
