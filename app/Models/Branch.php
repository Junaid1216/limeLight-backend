<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function transactionSummaries()
    {
        return $this->hasMany(TransactionSummary::class);
    }

    public function footfallDailySummaries()
    {
        return $this->hasMany(FootfallDailySummary::class);
    }
}
