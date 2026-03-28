<?php

namespace App\Console\Commands;

use App\Models\Company;
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
        $this->info('Syncing earnings calendar...');
        $from  = now()->toDateString();
        $to    = now()->addWeeks(4)->toDateString();
        $count = $this->service->syncEarningsCalendar($from, $to);
        $this->info("Synced {$count} earnings records.");
    }

    private function syncStocks(): void
    {
        $this->info('Refreshing stock prices...');
        Company::all()->each(function (Company $company) {
            try {
                $this->service->refreshStockPrice($company->symbol);
            } catch (\Exception $e) {
                $this->warn("Failed to refresh {$company->symbol}: {$e->getMessage()}");
            }
        });
        $this->info('Stock prices refreshed.');
    }

    private function syncSentiments(): void
    {
        $this->info('Syncing sentiments...');
        Company::all()->each(function (Company $company) {
            try {
                $this->service->syncSentiment($company->symbol);
            } catch (\Exception $e) {
                $this->warn("Failed sentiment {$company->symbol}: {$e->getMessage()}");
            }
        });
        $this->info('Sentiments synced.');
    }

    private function syncFinancials(): void
    {
        $this->info('Syncing company financials...');
        Company::all()->each(function (Company $company) {
            try {
                $this->service->syncCompanyFinancials($company->symbol);
            } catch (\Exception $e) {
                $this->warn("Failed financials {$company->symbol}: {$e->getMessage()}");
            }
        });
        $this->info('Financials synced.');
    }
}
