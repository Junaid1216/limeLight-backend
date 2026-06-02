<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function branchManager()
    {
        return $this->belongsTo(BranchManager::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
}
