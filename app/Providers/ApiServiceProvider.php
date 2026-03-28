<?php

namespace App\Providers;

use App\Contracts\MarketDataProviderInterface;
use App\Contracts\TradingProviderInterface;
use App\Providers\MarketData\FinnhubProvider;
use App\Providers\MarketData\MockMarketDataProvider;
use App\Providers\MarketData\PolygonProvider;
use App\Providers\Trading\AlpacaProvider;
use App\Providers\Trading\MockTradingProvider;
use App\Providers\Trading\MoomooProvider;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MarketDataProviderInterface::class, function () {
            if (config('trading.use_mock_providers')) {
                return new MockMarketDataProvider();
            }
            return match (config('trading.market_data_provider', 'finnhub')) {
                'polygon' => new PolygonProvider(),
                'mock'    => new MockMarketDataProvider(),
                default   => new FinnhubProvider(),
            };
        });

        $this->app->singleton(TradingProviderInterface::class, function () {
            if (config('trading.use_mock_providers')) {
                return new MockTradingProvider();
            }
            return match (config('trading.provider', 'alpaca')) {
                'moomoo' => new MoomooProvider(),
                'mock'   => new MockTradingProvider(),
                default  => new AlpacaProvider(),
            };
        });
    }
}
