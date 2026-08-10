<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingVideoCompletion extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function trainingVideo()
    {
        return $this->belongsTo(TrainingVideo::class, 'training_video_id');
    }
}
