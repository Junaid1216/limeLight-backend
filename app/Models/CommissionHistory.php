<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'commission' => 'float',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];
}
