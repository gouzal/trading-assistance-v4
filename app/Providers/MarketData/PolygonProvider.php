<?php

namespace App\Providers\MarketData;

use App\Contracts\MarketDataProviderInterface;
use App\DTOs\QuoteDTO;
use App\DTOs\EarningsDTO;
use App\DTOs\SentimentDTO;
use App\DTOs\CompanyProfileDTO;

class PolygonProvider implements MarketDataProviderInterface
{
    public function getQuote(string $symbol): QuoteDTO
    {
        throw new \RuntimeException('Polygon provider not yet implemented.');
    }

    public function getEarningsCalendar(string $from, string $to): array
    {
        throw new \RuntimeException('Polygon provider not yet implemented.');
    }

    public function getSentiment(string $symbol): SentimentDTO
    {
        throw new \RuntimeException('Polygon provider not yet implemented.');
    }

    public function getCompanyProfile(string $symbol): CompanyProfileDTO
    {
        throw new \RuntimeException('Polygon provider not yet implemented.');
    }

    public function getFinancialMetrics(string $symbol): array { return []; }
    public function getPriceTarget(string $symbol): ?float { return null; }
    public function getRevenueEstimate(string $symbol): ?float { return null; }
    public function getAnalystRecommendation(string $symbol): array { return []; }
    public function searchSymbol(string $query): array { return []; }
}
