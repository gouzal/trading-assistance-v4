<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradingOrder extends Model
{
    protected $fillable = [
        'user_id', 'symbol', 'order_type', 'order_class', 'quantity',
        'limit_price', 'executed_price', 'status', 'alpaca_order_id',
        'error_message', 'submitted_at', 'executed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
