<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Both runs sync earnings + quotes + sentiments + financials for companies
// with an earnings release in the next 31 days. Times are NY (America/New_York).
Schedule::command('financial:update --type=all')
    ->dailyAt('08:30')
    ->timezone('America/New_York')
    ->description('Morning financial data sync (NY 08:30)');

Schedule::command('financial:update --type=all')
    ->dailyAt('16:00')
    ->timezone('America/New_York')
    ->description('Afternoon financial data sync (NY 16:00)');
