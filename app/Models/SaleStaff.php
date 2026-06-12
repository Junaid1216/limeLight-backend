<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class SaleStaff extends Model
{
    use HasFactory, HasApiTokens;
    protected $guarded = [];

        public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
