<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function adminNotification()
    {
        return $this->belongsTo(AdminNotification::class, 'admin_notification_id');
    }

    public function resolveUser()
    {
        $map = [
            'staff' => SaleStaff::class,
            'manager' => BranchManager::class,
            'asm' => AreaSaleManager::class,
        ];

        $class = $map[$this->user_type] ?? null;
        if (!$class) {
            return null;
        }

        return $class::find($this->user_id);
    }
}
