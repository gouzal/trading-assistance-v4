<?php

namespace App\Services;

use App\Contracts\MarketDataProviderInterface;
use App\DTOs\QuoteDTO;
use App\DTOs\SentimentDTO;
use App\Models\Company;
use App\Models\CompanyFinancial;
use App\Models\Earning;
use App\Models\Sentiment;
use App\Models\StockPrice;
use Illuminate\Support\Facades\Cache;

class MarketDataService
{
    public function __construct(
        private MarketDataProviderInterface $provider
    ) {}

    public function getQuote(string $symbol): QuoteDTO
    {
        return Cache::remember(
            "trading_assistant.quote.{$symbol}",
            30,
            fn () => $this->provider->getQuote($symbol)
        );
    }

    public function getSentiment(string $symbol): SentimentDTO
    {
        return Cache::remember(
            "trading_assistant.sentiment.{$symbol}",
            3600,
            fn () => $this->provider->getSentiment($symbol)
        );
    }

    public function searchSymbols(string $query): array
    {
        $results = $this->provider->searchSymbol($query);

        if (empty($results)) {
            return [];
        }

        $symbols    = array_column($results, 'symbol');
        $favorites  = Company::whereIn('symbol', $symbols)
            ->where('is_favorite', true)
            ->pluck('symbol')
            ->flip()
            ->all();

        foreach ($results as &$item) {
            $item['is_favorite'] = isset($favorites[$item['symbol']]);
        }

        return $results;
    }

    public function syncEarningsCalendar(string $from, string $to): int
    {
        // Always fetch fresh — this runs twice daily, no need to cache DTOs
        $earnings = $this->provider->getEarningsCalendar($from, $to);

        // All companies in our registry, not just favorites
        $trackedSymbols = Company::pluck('symbol')->flip()->all();

        $count = 0;
        foreach ($earnings as $dto) {
            if (!isset($trackedSymbols[$dto->symbol])) {
                continue;
            }

            Earning::updateOrCreate(
                ['symbol' => $dto->symbol, 'announcement_date' => $dto->announcementDate],
                [
                    'announcement_time' => $dto->announcementTime,
                    'estimated_revenue' => $dto->estimatedRevenue,
                    'actual_revenue'    => $dto->actualRevenue,
                    'estimated_eps'     => $dto->estimatedEps,
                    'actual_eps'        => $dto->actualEps,
                    'api_data'          => $dto->rawData,
                ]
            );
            $count++;
        }
        return $count;
    }

    public function syncCompanyFinancials(string $symbol): void
    {
        $metrics = $this->provider->getFinancialMetrics($symbol);
        $target  = $this->provider->getPriceTarget($symbol);
        $revenue = $this->provider->getRevenueEstimate($symbol);
        $profile = Cache::remember(
            "trading_assistant.profile.{$symbol}",
            86400,
            fn () => $this->provider->getCompanyProfile($symbol)
        );

        CompanyFinancial::updateOrCreate(
            ['symbol' => $symbol],
            [
                'company_name'        => $profile->name,
                'pe_ratio'            => $metrics['peNormalizedAnnual'] ?? null,
                'pb_ratio'            => $metrics['pbAnnual'] ?? null,
                'peg_ratio'           => $metrics['pegNormalizedAnnual'] ?? null,
                'debt_to_equity'      => $metrics['debtToEquityAnnual'] ?? null,
                'profit_margin'       => $metrics['netProfitMarginAnnual'] ?? null,
                'week_52_high'        => $metrics['52WeekHigh'] ?? null,
                'week_52_low'         => $metrics['52WeekLow'] ?? null,
                'fair_value_estimate' => $target,
                'revenue_estimate'    => $revenue,
                'data_provider'       => 'finnhub',
                'last_updated'        => now(),
            ]
        );
    }

    public function syncSentiment(string $symbol): void
    {
        $dto = $this->provider->getSentiment($symbol);
        $rec = $this->provider->getAnalystRecommendation($symbol);

        $label = $this->mapAnalystRating($rec);

        Sentiment::updateOrCreate(
            ['symbol' => $symbol],
            [
                'sentiment_score' => $dto->sentimentScore,
                'sentiment_label' => $dto->sentimentLabel,
                'analyst_rating'  => $label,
                'buy_count'       => ($rec['buy'] ?? 0) + ($rec['strongBuy'] ?? 0),
                'hold_count'      => $rec['hold'] ?? 0,
                'sell_count'      => ($rec['sell'] ?? 0) + ($rec['strongSell'] ?? 0),
                'news_data'       => $dto->newsData,
            ]
        );
    }

    public function refreshStockPrice(string $symbol): void
    {
        $quote = $this->provider->getQuote($symbol);
        StockPrice::updateOrCreate(
            ['symbol' => $symbol],
            [
                'current_price'      => $quote->currentPrice,
                'day_open'           => $quote->dayOpen,
                'day_high'           => $quote->dayHigh,
                'day_low'            => $quote->dayLow,
                'day_change'         => $quote->dayChange,
                'day_change_percent' => $quote->dayChangePercent,
                'volume'             => $quote->volume,
                'last_updated'       => now(),
            ]
        );
    }

    private function mapAnalystRating(array $rec): string
    {
        $buy   = ($rec['buy'] ?? 0) + ($rec['strongBuy'] ?? 0);
        $sell  = ($rec['sell'] ?? 0) + ($rec['strongSell'] ?? 0);
        $hold  = $rec['hold'] ?? 0;
        $total = $buy + $sell + $hold;

        if ($total === 0) return 'No Rating';
        $buyRatio = $buy / $total;

        return match(true) {
            $buyRatio >= 0.7 => 'Strong Buy',
            $buyRatio >= 0.5 => 'Buy',
            $buyRatio >= 0.3 => 'Hold',
            $buyRatio >= 0.1 => 'Sell',
            default          => 'Strong Sell',
        };
    }
}
