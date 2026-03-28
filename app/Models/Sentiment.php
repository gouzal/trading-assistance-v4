<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sentiment extends Model
{
    protected $fillable = [
        'symbol', 'sentiment_score', 'sentiment_label', 'analyst_rating',
        'buy_count', 'hold_count', 'sell_count', 'news_data',
        'revised_expectations', 'revision_direction',
    ];

    protected $casts = [
        'news_data' => 'array',
        'revised_expectations' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'symbol', 'symbol');
    }
}
