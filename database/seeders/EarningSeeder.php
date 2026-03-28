<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class EarningSeeder extends Seeder
{
    public function run(): void
    {
        $now  = Carbon::now();
        $base = Carbon::today();

        $earnings = [
            // Upcoming (no actuals yet)
            ['symbol' => 'AAPL',  'announcement_date' => $base->copy()->addDays(3)->toDateString(),  'announcement_time' => 'AMC', 'estimated_revenue' => 94_500_000_000, 'actual_revenue' => null, 'estimated_eps' => 1.43, 'actual_eps' => null],
            ['symbol' => 'MSFT',  'announcement_date' => $base->copy()->addDays(7)->toDateString(),  'announcement_time' => 'AMC', 'estimated_revenue' => 61_000_000_000, 'actual_revenue' => null, 'estimated_eps' => 2.93, 'actual_eps' => null],
            ['symbol' => 'NVDA',  'announcement_date' => $base->copy()->addDays(10)->toDateString(), 'announcement_time' => 'AMC', 'estimated_revenue' => 24_000_000_000, 'actual_revenue' => null, 'estimated_eps' => 5.57, 'actual_eps' => null],
            ['symbol' => 'GOOGL', 'announcement_date' => $base->copy()->addDays(14)->toDateString(), 'announcement_time' => 'AMC', 'estimated_revenue' => 89_200_000_000, 'actual_revenue' => null, 'estimated_eps' => 1.97, 'actual_eps' => null],
            ['symbol' => 'META',  'announcement_date' => $base->copy()->addDays(14)->toDateString(), 'announcement_time' => 'AMC', 'estimated_revenue' => 43_500_000_000, 'actual_revenue' => null, 'estimated_eps' => 5.25, 'actual_eps' => null],
            ['symbol' => 'AMZN',  'announcement_date' => $base->copy()->addDays(21)->toDateString(), 'announcement_time' => 'AMC', 'estimated_revenue' => 187_000_000_000,'actual_revenue' => null, 'estimated_eps' => 1.36, 'actual_eps' => null],
            // Past (actuals filled in)
            ['symbol' => 'TSLA',  'announcement_date' => $base->copy()->subDays(10)->toDateString(), 'announcement_time' => 'AMC', 'estimated_revenue' => 26_100_000_000, 'actual_revenue' => 25_707_000_000, 'estimated_eps' => 0.71, 'actual_eps' => 0.66],
            ['symbol' => 'JPM',   'announcement_date' => $base->copy()->subDays(7)->toDateString(),  'announcement_time' => 'BMO', 'estimated_revenue' => 41_000_000_000, 'actual_revenue' => 42_730_000_000, 'estimated_eps' => 4.17, 'actual_eps' => 4.44],
            ['symbol' => 'V',     'announcement_date' => $base->copy()->subDays(5)->toDateString(),  'announcement_time' => 'AMC', 'estimated_revenue' => 9_600_000_000,  'actual_revenue' => 9_887_000_000,  'estimated_eps' => 2.66, 'actual_eps' => 2.75],
            ['symbol' => 'JNJ',   'announcement_date' => $base->copy()->subDays(3)->toDateString(),  'announcement_time' => 'BMO', 'estimated_revenue' => 22_300_000_000, 'actual_revenue' => 22_758_000_000, 'estimated_eps' => 2.57, 'actual_eps' => 2.69],
        ];

        foreach ($earnings as $row) {
            DB::table('earnings')->insertOrIgnore(array_merge($row, [
                'api_data'   => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
