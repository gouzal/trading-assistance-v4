<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Earning;
use App\Services\TradingService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private TradingService $tradingService) {}

    public function index(Request $request)
    {
        $weeks         = (int) $request->input('weeks', $request->user()->setting?->earnings_date_range ?? 2);
        $watchlistOnly = $request->boolean('watchlist_only');
        $sortSentiment = $request->boolean('sort_sentiment');
        $sortPrice     = $request->boolean('sort_price');

        $from = now()->toDateString();
        $to   = now()->addWeeks($weeks)->toDateString();

        $query = Earning::with(['company.financial', 'company.sentiment', 'company.stockPrice'])
            ->whereBetween('announcement_date', [$from, $to])
            ->orderBy('announcement_date');

        if ($watchlistOnly) {
            $query->whereHas('company', fn ($q) => $q->where('is_favorite', true));
        }

        $earnings = $query->get();

        // Apply price first so sentiment becomes the primary sort when both are active
        // (PHP 8+ stable sort preserves price order within equal sentiment buckets)
        if ($sortPrice) {
            $earnings = $earnings->sortBy(
                fn ($e) => $e->company?->stockPrice?->current_price ?? PHP_INT_MAX
            )->values();
        }

        if ($sortSentiment) {
            $earnings = $earnings->sortByDesc(function ($e) {
                $s     = $e->company?->sentiment;
                $total = ($s->buy_count ?? 0) + ($s->hold_count ?? 0) + ($s->sell_count ?? 0);
                return $total > 0 ? ($s->buy_count / $total) : -1;
            })->values();
        }

        $account   = null;
        $positions = [];

        try {
            $account   = $this->tradingService->getAccount();
            $positions = $this->tradingService->getPositions();
        } catch (\Exception) {
            // Trading API unavailable — continue without account data
        }

        return view('dashboard.index', compact('earnings', 'account', 'positions', 'weeks', 'watchlistOnly', 'sortSentiment', 'sortPrice'));
    }
}
