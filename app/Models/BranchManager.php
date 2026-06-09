<?php

namespace App\Models;

use App\Models\SaleStaff;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class BranchManager extends Model
{
    use HasFactory,  HasApiTokens;
    protected $guarded = [];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

        public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function saleStaff()
    {
        return $this->hasMany(SaleStaff::class, 'branch_manager_id');
    }

    public function targets()
{
    return $this->hasMany(Target::class);
}
}
