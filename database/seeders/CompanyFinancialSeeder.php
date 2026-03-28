<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CompanyFinancialSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $financials = [
            ['symbol' => 'AAPL',  'company_name' => 'Apple Inc.',            'market_cap' => 2_700_000_000_000, 'current_price' => 173.57, 'pe_ratio' => 28.5,  'pb_ratio' => 46.2,  'peg_ratio' => 2.8,  'debt_to_equity' => 1.76,  'profit_margin' => 0.2531, 'revenue_estimate' => 94_500_000_000,  'fair_value_estimate' => 185.00, 'week_52_high' => 199.62, 'week_52_low' => 124.17],
            ['symbol' => 'MSFT',  'company_name' => 'Microsoft Corporation', 'market_cap' => 3_080_000_000_000, 'current_price' => 415.32, 'pe_ratio' => 36.2,  'pb_ratio' => 12.4,  'peg_ratio' => 2.2,  'debt_to_equity' => 0.35,  'profit_margin' => 0.3561, 'revenue_estimate' => 61_000_000_000,  'fair_value_estimate' => 450.00, 'week_52_high' => 468.35, 'week_52_low' => 309.45],
            ['symbol' => 'NVDA',  'company_name' => 'NVIDIA Corporation',    'market_cap' => 2_150_000_000_000, 'current_price' => 875.40, 'pe_ratio' => 65.4,  'pb_ratio' => 38.6,  'peg_ratio' => 1.8,  'debt_to_equity' => 0.42,  'profit_margin' => 0.5259, 'revenue_estimate' => 24_000_000_000,  'fair_value_estimate' => 950.00, 'week_52_high' => 974.00, 'week_52_low' => 373.85],
            ['symbol' => 'GOOGL', 'company_name' => 'Alphabet Inc.',         'market_cap' => 2_190_000_000_000, 'current_price' => 175.98, 'pe_ratio' => 24.1,  'pb_ratio' => 6.2,   'peg_ratio' => 1.5,  'debt_to_equity' => 0.06,  'profit_margin' => 0.2306, 'revenue_estimate' => 89_200_000_000,  'fair_value_estimate' => 195.00, 'week_52_high' => 193.31, 'week_52_low' => 115.83],
            ['symbol' => 'AMZN',  'company_name' => 'Amazon.com Inc.',       'market_cap' => 2_010_000_000_000, 'current_price' => 191.25, 'pe_ratio' => 60.3,  'pb_ratio' => 8.7,   'peg_ratio' => 2.3,  'debt_to_equity' => 0.54,  'profit_margin' => 0.0855, 'revenue_estimate' => 187_000_000_000, 'fair_value_estimate' => 210.00, 'week_52_high' => 201.20, 'week_52_low' => 118.35],
            ['symbol' => 'META',  'company_name' => 'Meta Platforms Inc.',   'market_cap' => 1_290_000_000_000, 'current_price' => 502.10, 'pe_ratio' => 26.8,  'pb_ratio' => 7.9,   'peg_ratio' => 1.4,  'debt_to_equity' => 0.12,  'profit_margin' => 0.3439, 'revenue_estimate' => 43_500_000_000,  'fair_value_estimate' => 550.00, 'week_52_high' => 531.49, 'week_52_low' => 279.40],
            ['symbol' => 'TSLA',  'company_name' => 'Tesla Inc.',            'market_cap' =>   548_000_000_000, 'current_price' => 172.82, 'pe_ratio' => 47.6,  'pb_ratio' => 10.1,  'peg_ratio' => 3.5,  'debt_to_equity' => 0.17,  'profit_margin' => 0.0551, 'revenue_estimate' => 26_100_000_000,  'fair_value_estimate' => 150.00, 'week_52_high' => 299.29, 'week_52_low' => 138.80],
            ['symbol' => 'JPM',   'company_name' => 'JPMorgan Chase & Co.',  'market_cap' =>   568_000_000_000, 'current_price' => 197.45, 'pe_ratio' => 11.2,  'pb_ratio' => 1.9,   'peg_ratio' => 1.1,  'debt_to_equity' => null,  'profit_margin' => 0.3168, 'revenue_estimate' => 41_000_000_000,  'fair_value_estimate' => 210.00, 'week_52_high' => 220.82, 'week_52_low' => 135.19],
            ['symbol' => 'V',     'company_name' => 'Visa Inc.',             'market_cap' =>   578_000_000_000, 'current_price' => 279.30, 'pe_ratio' => 30.4,  'pb_ratio' => 14.6,  'peg_ratio' => 2.1,  'debt_to_equity' => 1.86,  'profit_margin' => 0.5402, 'revenue_estimate' => 9_600_000_000,   'fair_value_estimate' => 300.00, 'week_52_high' => 290.96, 'week_52_low' => 218.96],
            ['symbol' => 'JNJ',   'company_name' => 'Johnson & Johnson',     'market_cap' =>   355_000_000_000, 'current_price' => 147.65, 'pe_ratio' => 15.4,  'pb_ratio' => 4.7,   'peg_ratio' => 2.4,  'debt_to_equity' => 0.48,  'profit_margin' => 0.2024, 'revenue_estimate' => 22_300_000_000,  'fair_value_estimate' => 165.00, 'week_52_high' => 175.97, 'week_52_low' => 143.13],
        ];

        foreach ($financials as $row) {
            DB::table('company_financials')->updateOrInsert(
                ['symbol' => $row['symbol']],
                array_merge($row, [
                    'revenue_turnover_pct' => null,
                    'historic_high'        => null,
                    'historic_low'         => null,
                    'data_provider'        => 'mock',
                    'last_updated'         => $now,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ])
            );
        }
    }
}
