<?php

namespace App\Providers\MarketData;

use App\Contracts\MarketDataProviderInterface;
use App\DTOs\QuoteDTO;
use App\DTOs\EarningsDTO;
use App\DTOs\SentimentDTO;
use App\DTOs\CompanyProfileDTO;

class MockMarketDataProvider implements MarketDataProviderInterface
{
    public function getQuote(string $symbol): QuoteDTO
    {
        return new QuoteDTO($symbol, 150.00, 148.00, 152.00, 147.00, 2.00, 1.35, 1000000);
    }

    public function getEarningsCalendar(string $from, string $to): array
    {
        return [
            new EarningsDTO('AAPL', now()->addDays(3)->toDateString(), 'AMC', 94000000000, null, 1.43, null),
            new EarningsDTO('MSFT', now()->addDays(7)->toDateString(), 'AMC', 61000000000, null, 2.93, null),
        ];
    }

    public function getSentiment(string $symbol): SentimentDTO
    {
        return new SentimentDTO($symbol, 0.45, 'Good', 'Buy', 20, 8, 3, []);
    }

    public function getCompanyProfile(string $symbol): CompanyProfileDTO
    {
        return new CompanyProfileDTO($symbol, $symbol . ' Inc.', 'Technology', 'Software', 'US', null);
    }

    public function getFinancialMetrics(string $symbol): array
    {
        return ['peNormalizedAnnual' => 28.5, 'pbAnnual' => 3.2, 'debtToEquityAnnual' => 0.5];
    }

    public function getPriceTarget(string $symbol): ?float { return 175.00; }
    public function getRevenueEstimate(string $symbol): ?float { return 94000000000.0; }
    public function getAnalystRecommendation(string $symbol): array
    {
        return ['buy' => 20, 'hold' => 8, 'sell' => 3, 'strongBuy' => 10, 'strongSell' => 1];
    }
}
