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

        $from = now()->toDateString();
        $to   = now()->addWeeks($weeks)->toDateString();

        $query = Earning::with(['company.financial', 'company.sentiment', 'company.stockPrice'])
            ->whereBetween('announcement_date', [$from, $to])
            ->orderBy('announcement_date');

        if ($watchlistOnly) {
            $query->whereHas('company', fn ($q) => $q->where('is_favorite', true));
        }

        $earnings  = $query->get();
        $account   = null;
        $positions = [];

        try {
            $account   = $this->tradingService->getAccount();
            $positions = $this->tradingService->getPositions();
        } catch (\Exception) {
            // Trading API unavailable — continue without account data
        }

        return view('dashboard.index', compact('earnings', 'account', 'positions', 'weeks', 'watchlistOnly'));
    }
}
