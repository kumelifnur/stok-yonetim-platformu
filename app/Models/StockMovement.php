<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'business_id', 'product_id', 'sale_id', 'product_name', 'type',
        'quantity_delta', 'quantity_after', 'note',
    ];

    protected function casts(): array
    {
        return ['quantity_delta' => 'integer', 'quantity_after' => 'integer'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
