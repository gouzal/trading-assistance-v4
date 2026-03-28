<?php

namespace App\Contracts;

use App\DTOs\QuoteDTO;
use App\DTOs\EarningsDTO;
use App\DTOs\SentimentDTO;
use App\DTOs\CompanyProfileDTO;

interface MarketDataProviderInterface
{
    public function getQuote(string $symbol): QuoteDTO;
    public function getEarningsCalendar(string $from, string $to): array; // EarningsDTO[]
    public function getSentiment(string $symbol): SentimentDTO;
    public function getCompanyProfile(string $symbol): CompanyProfileDTO;
    public function getFinancialMetrics(string $symbol): array;
    public function getPriceTarget(string $symbol): ?float;
    public function getRevenueEstimate(string $symbol): ?float;
    public function getAnalystRecommendation(string $symbol): array;

    /** @return array<int, array{symbol: string, name: string, logo_url: null}> */
    public function searchSymbol(string $query): array;
}
