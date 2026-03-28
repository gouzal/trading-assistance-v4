@extends('layouts.app')
@section('title', 'Watchlist')

@section('content')
<div class="max-w-4xl mx-auto px-4 pt-6 pb-24"
     x-data="{
         query: '',
         results: [],
         searching: false,
         async search() {
             if (this.query.length < 1) { this.results = []; return; }
             this.searching = true;
             try {
                 const res = await fetch('/api/symbols/search?q=' + encodeURIComponent(this.query), {
                     headers: { 'Accept': 'application/json' }
                 });
                 this.results = await res.json();
             } catch(e) { this.results = []; }
             finally { this.searching = false; }
         }
     }">

    {{-- Search --}}
    <section class="mb-8">
        <div class="relative group">
            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-outline">search</span>
            </div>
            <input type="text"
                   x-model="query"
                   @input.debounce.300ms="search()"
                   placeholder="Search by symbol or company name..."
                   class="w-full h-14 pl-12 pr-4 bg-surface-container border-none rounded-xl focus:ring-2 focus:ring-primary text-on-surface placeholder:text-on-surface-variant transition-all outline-none">
        </div>
    </section>

    {{-- Search Results --}}
    <section x-show="results.length > 0 || searching" class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold tracking-widest text-on-surface-variant uppercase">Results</h2>
            <span class="text-xs text-outline" x-text="results.length + ' matches found'"></span>
        </div>
        <div x-show="searching" class="text-center py-4 text-on-surface-variant text-sm">Searching...</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="company in results" :key="company.symbol">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-outline-variant flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center overflow-hidden">
                                <img x-show="company.logo_url" :src="company.logo_url" :alt="company.symbol" class="w-8 h-8 object-contain">
                                <span x-show="!company.logo_url" class="font-bold text-primary text-xs" x-text="company.symbol.substring(0,2)"></span>
                            </div>
                            <div>
                                <h3 class="font-bold text-on-surface" x-text="company.symbol"></h3>
                                <p class="text-xs text-on-surface-variant" x-text="company.name"></p>
                            </div>
                        </div>
                    </div>
                    <template x-if="company.is_favorite">
                        <div class="w-full py-2.5 rounded-lg bg-surface-container-highest text-on-surface-variant text-sm font-semibold flex items-center justify-center gap-2 border border-outline-variant cursor-default">
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">check</span>
                            ALREADY WATCHING
                        </div>
                    </template>
                    <template x-if="!company.is_favorite">
                        <form method="POST" action="{{ route('companies.store') }}">
                            @csrf
                            <input type="hidden" name="symbol" :value="company.symbol">
                            <button type="submit"
                                class="w-full py-2.5 rounded-lg bg-primary text-on-primary text-sm font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-transform">
                                <span class="material-symbols-outlined text-sm">add</span>
                                ADD TO WATCHLIST
                            </button>
                        </form>
                    </template>
                </div>
            </template>
        </div>
    </section>

    {{-- Currently Watching --}}
    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold tracking-widest text-on-surface-variant uppercase">Currently Watching</h2>
            <span class="text-xs text-outline">{{ $companies->where('is_favorite', true)->count() }} companies</span>
        </div>

        @if($companies->isEmpty())
            <div class="text-center py-16 text-on-surface-variant">
                <span class="material-symbols-outlined text-5xl mb-4 block">star_border</span>
                <p class="font-medium">No companies added yet. Search above to add some.</p>
            </div>
        @else
        <div class="space-y-3">
            @foreach($companies as $company)
            @php
                $price = $company->stockPrice;
                $changePositive = ($price?->day_change_percent ?? 0) >= 0;
            @endphp
            <div class="group bg-white p-4 rounded-xl border {{ $company->is_favorite ? 'border-primary/30 bg-white/40' : 'border-outline-variant' }} flex items-center justify-between hover:border-primary transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-surface-container flex items-center justify-center bg-surface-container">
                        @if($company->logo_url)
                            <img src="{{ $company->logo_url }}" alt="{{ $company->symbol }}" class="w-full h-full object-contain">
                        @else
                            <span class="font-bold text-primary">{{ substr($company->symbol, 0, 2) }}</span>
                        @endif
                    </div>
                    <div>
                        <p class="font-extrabold text-on-surface">{{ $company->symbol }}</p>
                        <p class="text-xs text-on-surface-variant">{{ $company->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    @if($price)
                    <div class="text-right">
                        <p class="font-bold text-on-surface">${{ number_format($price->current_price, 2) }}</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded {{ $changePositive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} text-xs font-bold">
                            {{ $changePositive ? '+' : '' }}{{ number_format($price->day_change_percent, 2) }}%
                        </span>
                    </div>
                    @endif
                    <div class="flex items-center gap-1">
                        <form method="POST" action="{{ route('companies.favorite', $company->symbol) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="p-2 hover:bg-surface-container rounded-lg transition-colors" title="Toggle favorite">
                                <span class="material-symbols-outlined text-sm {{ $company->is_favorite ? 'text-tertiary' : 'text-outline' }}"
                                      style="{{ $company->is_favorite ? \"font-variation-settings: 'FILL' 1;\" : '' }}">star</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('companies.destroy', $company->symbol) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 hover:bg-red-50 rounded-lg transition-colors"
                                    onclick="return confirm('Remove {{ $company->symbol }}?')" title="Remove">
                                <span class="material-symbols-outlined text-sm text-outline hover:text-error">delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </section>
</div>
@endsection
