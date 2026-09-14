<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * Line items matched by sale_key (preferred when present).
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_key', 'sale_key');
    }

    /**
     * Legacy / fallback line items matched by invoice_id.
     */
    public function itemsByInvoice()
    {
        return $this->hasMany(SaleItem::class, 'invoice_id', 'invoice_id');
    }
}
