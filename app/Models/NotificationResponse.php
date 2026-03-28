<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationResponse extends Model
{
    protected $fillable = ['user_id', 'symbol', 'action', 'days_to_earnings'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // "GOOGL (-25 days)" or "GOOGL (N/A)"
    public function getDisplayLabelAttribute(): string
    {
        if ($this->action === 'dismiss' || $this->days_to_earnings === null) {
            return "{$this->symbol} (N/A)";
        }
        return "{$this->symbol} (-{$this->days_to_earnings} days)";
    }
}
