<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'business_id', 'sku', 'name', 'description', 'unit_price', 'unit_cost',
        'stock_on_hand', 'reorder_level', 'is_active',
    ];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'unit_cost' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function saleLines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
