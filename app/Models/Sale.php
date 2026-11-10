<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'business_id', 'order_number', 'customer_name', 'customer_email', 'note', 'total', 'status',
    ];

    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }
}
