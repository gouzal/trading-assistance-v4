@extends('layouts.app')
@section('title', 'Watchlist')

@section('content')
<div class="max-w-4xl mx-auto px-4 pt-6 pb-24"
     x-data="{
         tab: new URLSearchParams(window.location.search).get('tab') || 'search',
         query: '',
         results: [],
         searching: false,
         noResults: false,
         async search() {
             if (this.query.length < 1) { this.results = []; this.noResults = false; return; }
             this.searching = true;
             this.noResults = false;
             try {
                 const res = await fetch('/api/symbols/search?q=' + encodeURIComponent(this.query), {
                     headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                 });
                 this.results = await res.json();
                 this.noResults = this.results.length === 0;
             } catch(e) { this.results = []; }
             finally { this.searching = false; }
         }
     }">

    {{-- Page Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-on-surface mb-1">Companies</h2>
        <p class="text-sm text-on-surface-variant">Search Finnhub to discover companies and manage your watchlist.</p>
    </div>

    {{-- Tabs --}}
    <div class="flex bg-surface-container rounded-xl p-1 mb-6 w-fit gap-1">
        <button @click="tab = 'search'"
                :class="tab === 'search' ? 'bg-white shadow-sm text-primary font-semibold' : 'text-on-surface-variant font-medium hover:text-on-surface'"
                class="flex items-center gap-1.5 px-5 py-2 rounded-lg text-sm transition-all">
            <span class="material-symbols-outlined text-sm">search</span>
            Search
        </button>
        <button @click="tab = 'watchlist'"
                :class="tab === 'watchlist' ? 'bg-white shadow-sm text-primary font-semibold' : 'text-on-surface-variant font-medium hover:text-on-surface'"
                class="flex items-center gap-1.5 px-5 py-2 rounded-lg text-sm transition-all">
            <span class="material-symbols-outlined text-sm">star</span>
            Companies
            @php $favoriteCount = $companies->where('is_favorite', true)->count(); @endphp
            @if($favoriteCount > 0)
                <span class="bg-primary/10 text-primary text-xs font-bold px-2 py-0.5 rounded-full leading-none">{{ $favoriteCount }}</span>
            @endif
        </button>
    </div>

    {{-- ── Search Tab ────────────────────────────────────────────── --}}
    <div x-show="tab === 'search'" x-transition.opacity.duration.150ms>

        {{-- Input --}}
        <div class="relative mb-6">
            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-outline">search</span>
            </div>
            <input type="text"
                   x-model="query"
                   @input.debounce.350ms="search()"
                   placeholder="Ticker or company name — e.g. AAPL, Tesla…"
                   class="w-full h-14 pl-12 pr-4 bg-surface-container border-none rounded-xl focus:ring-2 focus:ring-primary text-on-surface placeholder:text-on-surface-variant transition-all outline-none text-sm">
        </div>

        {{-- Idle state --}}
        <div x-show="!searching && results.length === 0 && !noResults" class="text-center py-16 text-on-surface-variant">
            <span class="material-symbols-outlined text-5xl mb-3 block opacity-40">manage_search</span>
            <p class="text-sm font-medium">Type above to search Finnhub in real time</p>
        </div>

        {{-- Spinner --}}
        <div x-show="searching" class="text-center py-10 text-on-surface-variant">
            <span class="material-symbols-outlined text-3xl mb-2 block animate-spin">progress_activity</span>
            <p class="text-sm">Searching Finnhub…</p>
        </div>

        {{-- No results --}}
        <div x-show="!searching && noResults" class="text-center py-12 text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl mb-2 block opacity-40">search_off</span>
            <p class="text-sm font-medium">No results for "<span x-text="query" class="font-bold"></span>"</p>
        </div>

        {{-- Results list --}}
        <div x-show="results.length > 0 && !searching" class="space-y-2">
            <p class="text-xs text-on-surface-variant font-semibold uppercase tracking-widest mb-4"
               x-text="results.length + ' result' + (results.length !== 1 ? 's' : '') + ' from Finnhub'"></p>

            <template x-for="company in results" :key="company.symbol">
                <div class="bg-white rounded-xl border border-outline-variant px-4 py-3 flex items-center justify-between gap-4 hover:border-primary/40 transition-colors">

                    {{-- Logo + name --}}
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center overflow-hidden flex-shrink-0">
                            <img x-show="company.logo_url" :src="company.logo_url" :alt="company.symbol" class="w-8 h-8 object-contain">
                            <span x-show="!company.logo_url"
                                  class="font-bold text-primary text-xs"
                                  x-text="company.symbol.substring(0, 2)"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-on-surface text-sm" x-text="company.symbol"></p>
                            <p class="text-xs text-on-surface-variant truncate" x-text="company.name"></p>
                        </div>
                    </div>

                    {{-- Action --}}
                    <div class="flex-shrink-0">
                        <template x-if="company.is_favorite">
                            <div class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container text-on-surface-variant text-xs font-semibold">
                                <span class="material-symbols-outlined text-sm text-primary" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                Watching
                            </div>
                        </template>
                        <template x-if="!company.is_favorite">
                            <form method="POST" action="{{ route('companies.store') }}">
                                @csrf
                                <input type="hidden" name="symbol" :value="company.symbol">
                                <button type="submit"
                                        class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-primary text-on-primary text-xs font-semibold hover:opacity-90 active:scale-95 transition-all">
                                    <span class="material-symbols-outlined text-sm">add</span>
                                    Add to Watchlist
                                </button>
                            </form>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ── Watchlist Tab ─────────────────────────────────────────── --}}
    <div x-show="tab === 'watchlist'" x-transition.opacity.duration.150ms>

        @if($companies->isEmpty())
            <div class="text-center py-16 text-on-surface-variant">
                <span class="material-symbols-outlined text-5xl mb-4 block opacity-40">star_border</span>
                <p class="font-medium mb-3 text-sm">Your watchlist is empty.</p>
                <button @click="tab = 'search'"
                        class="text-sm font-semibold text-primary underline underline-offset-2">
                    Go to Search tab to add companies
                </button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($companies as $company)
                @php
                    $price = $company->stockPrice;
                    $changePositive = ($price?->day_change_percent ?? 0) >= 0;
                @endphp
                <div class="bg-white p-4 rounded-xl border {{ $company->is_favorite ? 'border-primary/30' : 'border-outline-variant' }} flex items-center justify-between gap-4 hover:border-primary/50 transition-colors">

                    {{-- Logo + name --}}
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-surface-container flex items-center justify-center bg-surface-container flex-shrink-0">
                            @if($company->logo_url)
                                <img src="{{ $company->logo_url }}" alt="{{ $company->symbol }}" class="w-full h-full object-contain">
                            @else
                                <span class="font-bold text-primary">{{ substr($company->symbol, 0, 2) }}</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="font-extrabold text-on-surface">{{ $company->symbol }}</p>
                            <p class="text-xs text-on-surface-variant truncate">{{ $company->name }}</p>
                        </div>
                    </div>

                    {{-- Price + actions --}}
                    <div class="flex items-center gap-3 flex-shrink-0">
                        @if($price)
                        <div class="text-right">
                            <p class="font-bold text-on-surface text-sm">${{ number_format($price->current_price, 2) }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded {{ $changePositive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} text-xs font-bold">
                                {{ $changePositive ? '+' : '' }}{{ number_format($price->day_change_percent, 2) }}%
                            </span>
                        </div>
                        @endif

                        <div class="flex items-center gap-1">
                            <form method="POST" action="{{ route('companies.favorite', $company->symbol) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="p-2 hover:bg-surface-container rounded-lg transition-colors" title="Toggle favorite">
                                    <span class="material-symbols-outlined text-sm {{ $company->is_favorite ? 'text-primary' : 'text-outline' }}"
                                          style="{{ $company->is_favorite ? "font-variation-settings: 'FILL' 1;" : '' }}">star</span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('companies.destroy', $company->symbol) }}">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Remove {{ $company->symbol }} from watchlist?')"
                                        class="p-2 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                                    <span class="material-symbols-outlined text-sm text-outline hover:text-error">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
