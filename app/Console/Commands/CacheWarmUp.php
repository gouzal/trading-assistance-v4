<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\MarketDataService;
use Illuminate\Console\Command;

class CacheWarmUp extends Command
{
    protected $signature   = 'cache:warm-up';
    protected $description = 'Pre-warm file cache for all tracked companies';

    public function __construct(private MarketDataService $service) { parent::__construct(); }

    public function handle(): int
    {
        $companies = Company::all();
        $this->info("Warming cache for {$companies->count()} companies...");
        $bar = $this->output->createProgressBar($companies->count());

        foreach ($companies as $company) {
            try {
                $this->service->getQuote($company->symbol);
                $this->service->getSentiment($company->symbol);
            } catch (\Exception $e) {
                $this->warn(" Failed {$company->symbol}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Cache warm-up complete.');
        return self::SUCCESS;
    }
}
