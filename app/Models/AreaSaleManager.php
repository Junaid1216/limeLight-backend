<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class AreaSaleManager extends Model
{
    use HasFactory, HasApiTokens;
    protected $guarded = [];

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

        public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

        public function branchManagers()
    {
        return $this->hasMany(BranchManager::class, 'asm_id');
    }
}
