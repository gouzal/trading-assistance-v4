<?php

namespace App\Http\Controllers;

use App\Http\Requests\WatchlistRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\MarketDataService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(private MarketDataService $marketDataService) {}

    public function index()
    {
        $companies = Company::with(['financial', 'sentiment', 'stockPrice'])
            ->orderByDesc('is_favorite')
            ->orderBy('symbol')
            ->get();

        return view('companies.index', compact('companies'));
    }

    public function store(WatchlistRequest $request)
    {
        $symbol  = strtoupper($request->validated('symbol'));
        $profile = $this->marketDataService->getQuote($symbol); // validates symbol exists

        $company = Company::firstOrCreate(
            ['symbol' => $symbol],
            ['name' => $symbol, 'added_by' => 'user', 'is_favorite' => true]
        );

        if (!$company->wasRecentlyCreated) {
            $company->update(['is_favorite' => true]);
        }

        return redirect()->route('companies.index')->with('success', "{$symbol} added to watchlist.");
    }

    public function destroy(string $symbol)
    {
        Company::where('symbol', strtoupper($symbol))->delete();
        return redirect()->route('companies.index')->with('success', 'Company removed.');
    }

    public function toggleFavorite(string $symbol)
    {
        $company = Company::where('symbol', strtoupper($symbol))->firstOrFail();
        $company->update(['is_favorite' => !$company->is_favorite]);
        return back()->with('success', 'Favorite updated.');
    }

    public function search(Request $request)
    {
        $q = strtoupper(trim($request->string('q')));
        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $companies = Company::where('symbol', 'like', "{$q}%")
            ->orWhere('name', 'like', "%{$q}%")
            ->limit(10)
            ->get(['symbol', 'name', 'logo_url']);

        return CompanyResource::collection($companies);
    }

    public function quote(string $symbol)
    {
        $quote = $this->marketDataService->getQuote(strtoupper($symbol));
        return response()->json($quote);
    }
}
