<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTarget extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'seen' => 'boolean',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function targetable()
    {
        return $this->morphTo();
    }
}
