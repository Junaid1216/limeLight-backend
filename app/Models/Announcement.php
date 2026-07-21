<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'roles' => 'array',
        'status' => 'boolean',
    ];

    public static function categories(): array
    {
        return [
            'hr' => 'HR',
            'performance' => 'Performance',
            'promotions' => 'Promotions',
        ];
    }

    public static function roleOptions(): array
    {
        return [
            'asm' => 'Area Sale Manager',
            'branch_manager' => 'Branch Manager',
            'sales_staff' => 'Sales Staff',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? ucfirst($this->category);
    }
}
