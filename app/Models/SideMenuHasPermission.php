<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SideMenuHasPermission extends Model
{
    use HasFactory;
     protected $table = 'side_menu_has_permissions';

   protected $fillable = ['side_menu_id', 'permission_id'];
    public $timestamps = false;


    public function sideMenu()
    {
        return $this->belongsTo(SideMenue::class, 'side_menu_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}
