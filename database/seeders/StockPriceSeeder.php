<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StockPriceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $prices = [
            ['symbol' => 'AAPL',  'current_price' => 173.57, 'day_open' => 171.20, 'day_high' => 174.30, 'day_low' => 170.85, 'day_change' =>  2.37, 'day_change_percent' =>  1.38, 'volume' => 58_420_000],
            ['symbol' => 'MSFT',  'current_price' => 415.32, 'day_open' => 412.50, 'day_high' => 416.80, 'day_low' => 411.90, 'day_change' =>  2.82, 'day_change_percent' =>  0.68, 'volume' => 22_310_000],
            ['symbol' => 'NVDA',  'current_price' => 875.40, 'day_open' => 860.00, 'day_high' => 880.25, 'day_low' => 855.10, 'day_change' => 15.40, 'day_change_percent' =>  1.79, 'volume' => 41_650_000],
            ['symbol' => 'GOOGL', 'current_price' => 175.98, 'day_open' => 174.00, 'day_high' => 176.50, 'day_low' => 173.20, 'day_change' =>  1.98, 'day_change_percent' =>  1.14, 'volume' => 19_870_000],
            ['symbol' => 'AMZN',  'current_price' => 191.25, 'day_open' => 189.00, 'day_high' => 192.40, 'day_low' => 188.75, 'day_change' =>  2.25, 'day_change_percent' =>  1.19, 'volume' => 31_200_000],
            ['symbol' => 'META',  'current_price' => 502.10, 'day_open' => 498.00, 'day_high' => 504.50, 'day_low' => 496.30, 'day_change' =>  4.10, 'day_change_percent' =>  0.82, 'volume' => 15_430_000],
            ['symbol' => 'TSLA',  'current_price' => 172.82, 'day_open' => 178.00, 'day_high' => 178.50, 'day_low' => 171.30, 'day_change' => -5.18, 'day_change_percent' => -2.91, 'volume' => 89_750_000],
            ['symbol' => 'JPM',   'current_price' => 197.45, 'day_open' => 196.00, 'day_high' => 198.30, 'day_low' => 195.60, 'day_change' =>  1.45, 'day_change_percent' =>  0.74, 'volume' => 9_820_000],
            ['symbol' => 'V',     'current_price' => 279.30, 'day_open' => 277.50, 'day_high' => 280.10, 'day_low' => 276.80, 'day_change' =>  1.80, 'day_change_percent' =>  0.65, 'volume' => 6_340_000],
            ['symbol' => 'JNJ',   'current_price' => 147.65, 'day_open' => 148.20, 'day_high' => 149.00, 'day_low' => 147.00, 'day_change' => -0.55, 'day_change_percent' => -0.37, 'volume' => 7_120_000],
        ];

        foreach ($prices as $row) {
            DB::table('stock_prices')->updateOrInsert(
                ['symbol' => $row['symbol']],
                array_merge($row, ['last_updated' => $now])
            );
        }
    }
}
