<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockPrice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'symbol', 'current_price', 'day_open', 'day_high', 'day_low',
        'day_change', 'day_change_percent', 'volume', 'last_updated',
    ];

    protected $casts = ['last_updated' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'symbol', 'symbol');
    }
}
