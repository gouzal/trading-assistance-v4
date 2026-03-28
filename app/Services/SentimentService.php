<?php

namespace App\Services;

use App\Models\Sentiment;

class SentimentService
{
    public function getSentimentColor(string $label): string
    {
        return match($label) {
            'Very Good' => 'green',
            'Good'      => 'emerald',
            'Neutral'   => 'yellow',
            'Bad'       => 'orange',
            'Very Bad'  => 'red',
            default     => 'gray',
        };
    }

    public function getLatest(string $symbol): ?Sentiment
    {
        return Sentiment::where('symbol', $symbol)->latest()->first();
    }
}
