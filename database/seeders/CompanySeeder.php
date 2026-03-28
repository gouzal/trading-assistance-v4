<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $companies = [
            ['symbol' => 'AAPL',  'name' => 'Apple Inc.',             'sector' => 'Technology',          'industry' => 'Consumer Electronics',       'country' => 'US', 'is_favorite' => true],
            ['symbol' => 'MSFT',  'name' => 'Microsoft Corporation',  'sector' => 'Technology',          'industry' => 'Software—Infrastructure',     'country' => 'US', 'is_favorite' => true],
            ['symbol' => 'NVDA',  'name' => 'NVIDIA Corporation',     'sector' => 'Technology',          'industry' => 'Semiconductors',              'country' => 'US', 'is_favorite' => true],
            ['symbol' => 'GOOGL', 'name' => 'Alphabet Inc.',          'sector' => 'Communication Svcs',  'industry' => 'Internet Content & Info',     'country' => 'US', 'is_favorite' => false],
            ['symbol' => 'AMZN',  'name' => 'Amazon.com Inc.',        'sector' => 'Consumer Cyclical',   'industry' => 'Internet Retail',             'country' => 'US', 'is_favorite' => false],
            ['symbol' => 'META',  'name' => 'Meta Platforms Inc.',    'sector' => 'Communication Svcs',  'industry' => 'Internet Content & Info',     'country' => 'US', 'is_favorite' => false],
            ['symbol' => 'TSLA',  'name' => 'Tesla Inc.',             'sector' => 'Consumer Cyclical',   'industry' => 'Auto Manufacturers',          'country' => 'US', 'is_favorite' => true],
            ['symbol' => 'JPM',   'name' => 'JPMorgan Chase & Co.',   'sector' => 'Financial Services',  'industry' => 'Banks—Diversified',           'country' => 'US', 'is_favorite' => false],
            ['symbol' => 'V',     'name' => 'Visa Inc.',              'sector' => 'Financial Services',  'industry' => 'Credit Services',             'country' => 'US', 'is_favorite' => false],
            ['symbol' => 'JNJ',   'name' => 'Johnson & Johnson',      'sector' => 'Healthcare',          'industry' => 'Drug Manufacturers—General',  'country' => 'US', 'is_favorite' => false],
        ];

        foreach ($companies as $company) {
            DB::table('companies')->insertOrIgnore(array_merge($company, [
                'logo_url'   => null,
                'notes'      => null,
                'added_by'   => 'seeder',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
