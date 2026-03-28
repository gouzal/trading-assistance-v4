<?php

namespace App\Providers\MarketData;

use App\Contracts\MarketDataProviderInterface;
use App\DTOs\QuoteDTO;
use App\DTOs\EarningsDTO;
use App\DTOs\SentimentDTO;
use App\DTOs\CompanyProfileDTO;
use App\Models\ApiLog;
use Illuminate\Support\Facades\Http;

class FinnhubProvider implements MarketDataProviderInterface
{
    private string $baseUrl = 'https://finnhub.io/api/v1';
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.finnhub.key');
    }

    private function get(string $endpoint, array $params = []): array
    {
        $start = microtime(true);
        $params['token'] = $this->apiKey;

        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}{$endpoint}", $params);
            $ms = (int) ((microtime(true) - $start) * 1000);

            if ($response->failed()) {
                ApiLog::record('finnhub', $endpoint, $params['symbol'] ?? null, 'failed', $ms, $response->body());
                return [];
            }

            ApiLog::record('finnhub', $endpoint, $params['symbol'] ?? null, 'success', $ms);
            return $response->json() ?? [];
        } catch (\Exception $e) {
            $ms = (int) ((microtime(true) - $start) * 1000);
            ApiLog::record('finnhub', $endpoint, $params['symbol'] ?? null, 'failed', $ms, $e->getMessage());
            return [];
        }
    }

    public function getQuote(string $symbol): QuoteDTO
    {
        $data = $this->get('/quote', ['symbol' => $symbol]);
        return new QuoteDTO(
            symbol: $symbol,
            currentPrice: (float) ($data['c'] ?? 0),
            dayOpen: (float) ($data['o'] ?? 0),
            dayHigh: (float) ($data['h'] ?? 0),
            dayLow: (float) ($data['l'] ?? 0),
            dayChange: (float) ($data['d'] ?? 0),
            dayChangePercent: (float) ($data['dp'] ?? 0),
            volume: (int) ($data['v'] ?? 0),
        );
    }

    public function getEarningsCalendar(string $from, string $to): array
    {
        $data = $this->get('/calendar/earnings', ['from' => $from, 'to' => $to]);
        $earnings = [];
        foreach ($data['earningsCalendar'] ?? [] as $item) {
            $earnings[] = new EarningsDTO(
                symbol: $item['symbol'],
                announcementDate: $item['date'],
                announcementTime: $item['hour'] ?? null,
                estimatedRevenue: isset($item['revenueEstimate']) ? (float) $item['revenueEstimate'] : null,
                actualRevenue: isset($item['revenueActual']) ? (float) $item['revenueActual'] : null,
                estimatedEps: isset($item['epsEstimate']) ? (float) $item['epsEstimate'] : null,
                actualEps: isset($item['epsActual']) ? (float) $item['epsActual'] : null,
                rawData: $item,
            );
        }
        return $earnings;
    }

    public function getSentiment(string $symbol): SentimentDTO
    {
        $news = $this->get('/news-sentiment', ['symbol' => $symbol]);
        $score = (float) ($news['sentiment']['score'] ?? 0);
        $label = match(true) {
            $score >= 0.6  => 'Very Good',
            $score >= 0.2  => 'Good',
            $score >= -0.2 => 'Neutral',
            $score >= -0.6 => 'Bad',
            default        => 'Very Bad',
        };
        $articles = array_slice($news['news'] ?? [], 0, 10);

        return new SentimentDTO(
            symbol: $symbol,
            sentimentScore: $score,
            sentimentLabel: $label,
            analystRating: null,
            buyCount: null,
            holdCount: null,
            sellCount: null,
            newsData: $articles,
        );
    }

    public function getCompanyProfile(string $symbol): CompanyProfileDTO
    {
        $data = $this->get('/stock/profile2', ['symbol' => $symbol]);
        return new CompanyProfileDTO(
            symbol: $symbol,
            name: $data['name'] ?? $symbol,
            sector: $data['finnhubIndustry'] ?? null,
            industry: $data['finnhubIndustry'] ?? null,
            country: $data['country'] ?? null,
            logoUrl: $data['logo'] ?? null,
        );
    }

    public function getFinancialMetrics(string $symbol): array
    {
        $data = $this->get('/stock/metric', ['symbol' => $symbol, 'metric' => 'all']);
        return $data['metric'] ?? [];
    }

    public function getPriceTarget(string $symbol): ?float
    {
        $data = $this->get('/stock/price-target', ['symbol' => $symbol]);
        return isset($data['targetMean']) ? (float) $data['targetMean'] : null;
    }

    public function getRevenueEstimate(string $symbol): ?float
    {
        $data = $this->get('/stock/revenue-estimate', ['symbol' => $symbol, 'freq' => 'annual']);
        $estimates = $data['data'] ?? [];
        return isset($estimates[0]['revenueAvg']) ? (float) $estimates[0]['revenueAvg'] : null;
    }

    public function getAnalystRecommendation(string $symbol): array
    {
        $data = $this->get('/stock/recommendation', ['symbol' => $symbol]);
        return $data[0] ?? [];
    }
}
