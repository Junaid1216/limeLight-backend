<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'roles' => 'array',
        'is_deleted' => 'boolean',
    ];

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class, 'admin_notification_id');
    }
}
