<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Earning;
use App\Services\MarketDataService;
use Illuminate\Console\Command;

class UpdateFinancialData extends Command
{
    protected $signature = 'financial:update
                            {--type=all : Type to update (all|earnings|stocks|sentiments)}
                            {--morning : Morning run (earnings + stocks)}
                            {--evening : Evening run (sentiments + financials)}';

    protected $description = 'Sync financial data from market data provider';

    public function __construct(private MarketDataService $service) { parent::__construct(); }

    public function handle(): int
    {
        $type = $this->option('type');

        if ($this->option('morning')) {
            $this->syncEarnings();
            $this->syncStocks();
            return self::SUCCESS;
        }

        if ($this->option('evening')) {
            $this->syncSentiments();
            $this->syncFinancials();
            return self::SUCCESS;
        }

        match($type) {
            'earnings'   => $this->syncEarnings(),
            'stocks'     => $this->syncStocks(),
            'sentiments' => $this->syncSentiments(),
            default      => $this->syncAll(),
        };

        return self::SUCCESS;
    }

    private function syncAll(): void
    {
        $this->syncEarnings();
        $this->syncStocks();
        $this->syncSentiments();
        $this->syncFinancials();
    }

    private function syncEarnings(): void
    {
        $this->info('Syncing earnings calendar (today → +31 days)...');
        $from  = now()->toDateString();
        $to    = now()->addDays(31)->toDateString();
        $count = $this->service->syncEarningsCalendar($from, $to);
        $this->info("Synced {$count} earnings records.");
    }

    /**
     * Symbols in our companies registry that have an earnings release in the next 31 days.
     * syncEarnings() must run first to populate this list.
     */
    private function upcomingEarningsSymbols(): array
    {
        $from = now()->toDateString();
        $to   = now()->addDays(31)->toDateString();

        return Earning::whereBetween('announcement_date', [$from, $to])
            ->whereHas('company')
            ->pluck('symbol')
            ->unique()
            ->values()
            ->all();
    }

    private function syncStocks(): void
    {
        $symbols = $this->upcomingEarningsSymbols();
        $this->info("Refreshing stock prices for " . count($symbols) . " companies with upcoming earnings...");

        foreach ($symbols as $symbol) {
            try {
                $this->service->refreshStockPrice($symbol);
                $this->line("  ✓ {$symbol}");
            } catch (\Exception $e) {
                $this->warn("  ✗ {$symbol}: {$e->getMessage()}");
            }
            // 1 call/company — pace at ~30/min to stay under the 60/min limit
            sleep(2);
        }
        $this->info('Stock prices refreshed.');
    }

    private function syncSentiments(): void
    {
        $symbols = $this->upcomingEarningsSymbols();
        $this->info("Syncing sentiments for " . count($symbols) . " companies with upcoming earnings...");

        foreach ($symbols as $symbol) {
            try {
                $this->service->syncSentiment($symbol);
                $this->line("  ✓ {$symbol}");
            } catch (\Exception $e) {
                $this->warn("  ✗ {$symbol}: {$e->getMessage()}");
            }
            // 2 calls/company (news-sentiment + recommendation) — pace at ~24/min
            sleep(3);
        }
        $this->info('Sentiments synced.');
    }

    private function syncFinancials(): void
    {
        $symbols = $this->upcomingEarningsSymbols();
        $this->info("Syncing financials for " . count($symbols) . " companies with upcoming earnings...");

        foreach ($symbols as $symbol) {
            try {
                $this->service->syncCompanyFinancials($symbol);
                $this->line("  ✓ {$symbol}");
            } catch (\Exception $e) {
                $this->warn("  ✗ {$symbol}: {$e->getMessage()}");
            }
            // 3-4 calls/company (metrics + price-target + revenue-estimate + profile) — pace at ~36/min
            sleep(5);
        }
        $this->info('Financials synced.');
    }
}
