<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubAdminPermission extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function sub_admin()
    {
        return $this->belongsTo(SubAdmin::class, 'sub_admin_id', 'id');
    }

    // Define the relationship with SideMenu
    public function side_menu()
    {
        return $this->belongsTo(SideMenu::class, 'side_menu_id', 'id');
    }
}
