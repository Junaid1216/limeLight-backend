<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SaleStaff;

class BranchManager extends Model
{
    use HasFactory;
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
