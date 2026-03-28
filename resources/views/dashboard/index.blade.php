@extends('layouts.app')
@section('title', 'Earnings Calendar')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Header --}}
    <section class="mb-10">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight text-on-surface mb-2">Earnings Calendar</h2>
                <p class="text-on-surface-variant max-w-2xl">High-impact financial reporting dates for your watchlist and top market movers.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                {{-- Sort toggles --}}
                @php
                    $baseParams = ['weeks' => $weeks, 'watchlist_only' => $watchlistOnly ? '1' : '0'];
                @endphp
                <a href="{{ route('dashboard', array_merge($baseParams, ['sort' => $sort === 'sentiment' ? null : 'sentiment'])) }}"
                    class="flex items-center gap-1.5 {{ $sort === 'sentiment' ? 'bg-secondary text-on-secondary' : 'bg-surface-container-high text-on-surface border border-outline-variant' }} px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    <span class="material-symbols-outlined text-sm">trending_up</span>
                    Buy Sentiment
                </a>
                <a href="{{ route('dashboard', array_merge($baseParams, ['sort' => $sort === 'price' ? null : 'price'])) }}"
                    class="flex items-center gap-1.5 {{ $sort === 'price' ? 'bg-secondary text-on-secondary' : 'bg-surface-container-high text-on-surface border border-outline-variant' }} px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    <span class="material-symbols-outlined text-sm">attach_money</span>
                    Price
                </a>

                {{-- Date range filter --}}
                <div class="flex items-center gap-2">
                    <div class="flex bg-surface-container rounded-xl p-1">
                        @foreach([1,2,3,4] as $w)
                            <a href="{{ route('dashboard', array_merge($baseParams, ['weeks' => $w])) }}"
                                class="{{ $weeks == $w ? 'bg-white shadow-sm text-primary font-semibold' : 'text-on-surface-variant font-medium' }} px-4 py-1.5 rounded-lg text-sm transition-all">
                                {{ $w }}w
                            </a>
                        @endforeach
                    </div>
                    <a href="{{ route('dashboard', array_merge($baseParams, ['watchlist_only' => $watchlistOnly ? '0' : '1'])) }}"
                        class="flex items-center gap-2 {{ $watchlistOnly ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface border border-outline-variant' }} px-4 py-2 rounded-xl font-medium transition-colors">
                        <span class="material-symbols-outlined text-sm">star</span>
                        {{ $watchlistOnly ? 'All' : 'Watchlist' }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Account Summary (if available) --}}
    @if($account)
    <div class="mb-8 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/30">
            <p class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold mb-1">Cash</p>
            <p class="text-lg font-extrabold text-on-surface">${{ number_format($account->cash, 2) }}</p>
        </div>
        <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/30">
            <p class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold mb-1">Portfolio</p>
            <p class="text-lg font-extrabold text-on-surface">${{ number_format($account->portfolioValue, 2) }}</p>
        </div>
        <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/30">
            <p class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold mb-1">Buying Power</p>
            <p class="text-lg font-extrabold text-on-surface">${{ number_format($account->buyingPower, 2) }}</p>
        </div>
        <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/30">
            <p class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold mb-1">Mode</p>
            <p class="text-lg font-extrabold {{ $account->isPaperAccount ? 'text-tertiary' : 'text-primary' }}">
                {{ $account->isPaperAccount ? 'Paper' : 'Live' }}
            </p>
        </div>
    </div>
    @endif

    {{-- Earnings Grid --}}
    @if($earnings->isEmpty())
        <div class="text-center py-20 text-on-surface-variant">
            <span class="material-symbols-outlined text-5xl mb-4 block">calendar_today</span>
            <p class="text-lg font-medium">No earnings scheduled in this range.</p>
        </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($earnings as $earning)
            @php
                $company    = $earning->company;
                $isFavorite = $company?->is_favorite;
                $financial  = $company?->financial;
                $sentiment  = $company?->sentiment;
                $price      = $company?->stockPrice;
                $changePositive = ($price?->day_change_percent ?? 0) >= 0;

                $bullishPct = 0;
                if ($sentiment && (($sentiment->buy_count ?? 0) + ($sentiment->hold_count ?? 0) + ($sentiment->sell_count ?? 0)) > 0) {
                    $total = $sentiment->buy_count + $sentiment->hold_count + $sentiment->sell_count;
                    $bullishPct = $total > 0 ? round(($sentiment->buy_count / $total) * 100) : 0;
                }

                $timeLabel = match($earning->announcement_time) {
                    'BMO' => 'Before Market Open',
                    'AMC' => 'After Market Close',
                    'DMH' => 'During Market Hours',
                    default => $earning->announcement_time ?? '',
                };
            @endphp

            <div class="{{ $isFavorite ? 'border-2 border-tertiary-container shadow-xl' : 'border border-outline-variant shadow-md' }} bg-surface-container-lowest rounded-2xl overflow-hidden flex flex-col relative group transition-all hover:shadow-2xl"
                 x-data="{ showModal: false }">

                <div class="p-6 flex-1">
                    {{-- Company header --}}
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-surface-container flex items-center justify-center overflow-hidden">
                                @if($company?->logo_url)
                                    <img src="{{ $company->logo_url }}" alt="{{ $earning->symbol }}" class="w-8 h-8 object-contain">
                                @else
                                    <span class="font-bold text-primary text-sm">{{ substr($earning->symbol, 0, 2) }}</span>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-xl font-bold leading-tight">{{ $earning->symbol }}</h3>
                                <p class="text-xs text-on-surface-variant font-medium">{{ $company?->name ?? $earning->symbol }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            @if($isFavorite)
                                <span class="material-symbols-outlined text-tertiary text-lg leading-none" style="font-variation-settings: 'FILL' 1;">star</span>
                            @endif
                            <div class="text-sm font-bold text-primary">{{ $earning->announcement_date->format('M j') }} {{ $earning->announcement_time }}</div>
                            <div class="text-[10px] uppercase tracking-wider text-on-surface-variant">{{ $timeLabel }}</div>
                        </div>
                    </div>

                    {{-- Price --}}
                    <div class="flex items-baseline gap-2 mb-6">
                        <span class="text-2xl font-black">${{ $price ? number_format($price->current_price, 2) : '—' }}</span>
                        @if($price)
                            <span class="text-sm font-bold {{ $changePositive ? 'text-green-600' : 'text-error' }}">
                                {{ $changePositive ? '+' : '' }}{{ number_format($price->day_change_percent, 2) }}%
                            </span>
                        @endif
                    </div>

                    {{-- Metrics --}}
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-surface-container-low p-3 rounded-xl border border-outline-variant/30">
                            <span class="text-[10px] uppercase text-on-surface-variant block mb-1">Est. Revenue</span>
                            <span class="font-bold text-on-surface">
                                @if($financial?->revenue_estimate)
                                    ${{ number_format($financial->revenue_estimate / 1e9, 1) }}B
                                @elseif($earning->estimated_revenue)
                                    ${{ number_format($earning->estimated_revenue / 1e9, 1) }}B
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        <div class="bg-surface-container-low p-3 rounded-xl border border-outline-variant/30">
                            <span class="text-[10px] uppercase text-on-surface-variant block mb-1">Fair Value</span>
                            <span class="font-bold text-on-surface">
                                {{ $financial?->fair_value_estimate ? '$'.number_format($financial->fair_value_estimate, 2) : '—' }}
                            </span>
                        </div>
                    </div>

                    {{-- Sentiment --}}
                    @if($sentiment)
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-on-surface">Analyst Consensus</span>
                            <span class="text-xs font-black text-tertiary px-2 py-0.5 bg-tertiary-fixed rounded-full">
                                {{ $sentiment->analyst_rating ?? $sentiment->sentiment_label ?? '—' }}
                            </span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between items-center text-[10px] font-bold text-on-surface-variant">
                                <span>Bullish Sentiment</span>
                                <span>{{ $bullishPct }}%</span>
                            </div>
                            <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
                                <div class="h-full bg-tertiary" style="width: {{ $bullishPct }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="p-4 {{ $isFavorite ? 'bg-surface-container' : 'bg-surface-container-low' }} flex gap-3">
                    <button @click="showModal = true; $dispatch('open-trade-modal', { symbol: '{{ $earning->symbol }}', name: '{{ addslashes($company?->name ?? $earning->symbol) }}', price: '{{ $price?->current_price ?? 0 }}', side: 'buy' })"
                        class="flex-1 bg-primary text-on-primary py-3 rounded-xl font-bold text-sm tracking-wide shadow-md active:scale-95 transition-transform">
                        BUY
                    </button>
                    <button @click="showModal = true; $dispatch('open-trade-modal', { symbol: '{{ $earning->symbol }}', name: '{{ addslashes($company?->name ?? $earning->symbol) }}', price: '{{ $price?->current_price ?? 0 }}', side: 'sell' })"
                        class="flex-1 bg-on-surface text-surface py-3 rounded-xl font-bold text-sm tracking-wide shadow-md active:scale-95 transition-transform">
                        SELL
                    </button>
                </div>
            </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Trading Modal (global) --}}
@include('dashboard.partials.trading-modal')
@endsection
