<?php

return [
    'market_data_provider' => env('MARKET_DATA_PROVIDER', 'finnhub'),
    'provider'             => env('TRADING_PROVIDER', 'alpaca'),
    'use_mock_providers'   => env('USE_MOCK_PROVIDERS', false),
    'paper_mode'           => env('PAPER_TRADING_MODE', true),
    'enable_trading'       => env('ENABLE_TRADING', false),
    'max_position_size'    => env('MAX_POSITION_SIZE', 1000),
    'daily_trade_limit'    => env('DAILY_TRADE_LIMIT', 5),
];
