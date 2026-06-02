<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleStaff extends Model
{
    use HasFactory;
    protected $guarded = [];

        public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
}
