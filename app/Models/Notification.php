<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_type',
        'title',
        'description',
        'image',
        'is_sent',
        'sent_by',
        'delete_by_admin',
    ];

    public function targets()
    {
        return $this->hasMany(NotificationTarget::class);
    }
}
