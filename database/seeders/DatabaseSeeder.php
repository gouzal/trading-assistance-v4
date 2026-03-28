<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Order matters: companies must exist before FK-dependent tables
        $this->call([
            CompanySeeder::class,
            StockPriceSeeder::class,
            EarningSeeder::class,
            SentimentSeeder::class,
            CompanyFinancialSeeder::class,
        ]);
    }
}
