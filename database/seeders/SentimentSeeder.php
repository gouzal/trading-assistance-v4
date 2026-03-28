<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SentimentSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $sentiments = [
            ['symbol' => 'AAPL',  'sentiment_score' => 0.62,  'sentiment_label' => 'Bullish',  'analyst_rating' => 'Buy',         'buy_count' => 32, 'hold_count' => 8,  'sell_count' => 2,  'revised_expectations' => false, 'revision_direction' => null],
            ['symbol' => 'MSFT',  'sentiment_score' => 0.71,  'sentiment_label' => 'Bullish',  'analyst_rating' => 'Strong Buy',  'buy_count' => 38, 'hold_count' => 6,  'sell_count' => 1,  'revised_expectations' => true,  'revision_direction' => 'up'],
            ['symbol' => 'NVDA',  'sentiment_score' => 0.83,  'sentiment_label' => 'Bullish',  'analyst_rating' => 'Strong Buy',  'buy_count' => 41, 'hold_count' => 4,  'sell_count' => 1,  'revised_expectations' => true,  'revision_direction' => 'up'],
            ['symbol' => 'GOOGL', 'sentiment_score' => 0.45,  'sentiment_label' => 'Neutral',  'analyst_rating' => 'Buy',         'buy_count' => 28, 'hold_count' => 12, 'sell_count' => 3,  'revised_expectations' => false, 'revision_direction' => null],
            ['symbol' => 'AMZN',  'sentiment_score' => 0.55,  'sentiment_label' => 'Bullish',  'analyst_rating' => 'Buy',         'buy_count' => 35, 'hold_count' => 7,  'sell_count' => 2,  'revised_expectations' => false, 'revision_direction' => null],
            ['symbol' => 'META',  'sentiment_score' => 0.68,  'sentiment_label' => 'Bullish',  'analyst_rating' => 'Buy',         'buy_count' => 33, 'hold_count' => 9,  'sell_count' => 1,  'revised_expectations' => true,  'revision_direction' => 'up'],
            ['symbol' => 'TSLA',  'sentiment_score' => -0.12, 'sentiment_label' => 'Bearish',  'analyst_rating' => 'Hold',        'buy_count' => 14, 'hold_count' => 18, 'sell_count' => 12, 'revised_expectations' => true,  'revision_direction' => 'down'],
            ['symbol' => 'JPM',   'sentiment_score' => 0.38,  'sentiment_label' => 'Neutral',  'analyst_rating' => 'Buy',         'buy_count' => 22, 'hold_count' => 14, 'sell_count' => 4,  'revised_expectations' => false, 'revision_direction' => null],
            ['symbol' => 'V',     'sentiment_score' => 0.52,  'sentiment_label' => 'Bullish',  'analyst_rating' => 'Buy',         'buy_count' => 26, 'hold_count' => 10, 'sell_count' => 2,  'revised_expectations' => false, 'revision_direction' => null],
            ['symbol' => 'JNJ',   'sentiment_score' => 0.21,  'sentiment_label' => 'Neutral',  'analyst_rating' => 'Hold',        'buy_count' => 10, 'hold_count' => 20, 'sell_count' => 8,  'revised_expectations' => false, 'revision_direction' => null],
        ];

        $newsSample = [
            ['headline' => 'Q2 earnings beat estimates', 'source' => 'Reuters',   'datetime' => $now->copy()->subDays(2)->toISOString(), 'sentiment' => 'positive'],
            ['headline' => 'Analyst upgrades price target', 'source' => 'Bloomberg', 'datetime' => $now->copy()->subDays(1)->toISOString(), 'sentiment' => 'positive'],
        ];

        foreach ($sentiments as $row) {
            DB::table('sentiments')->insertOrIgnore(array_merge($row, [
                'news_data'  => json_encode($newsSample),
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
