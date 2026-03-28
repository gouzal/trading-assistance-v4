# TradingAssistant — Claude Instructions

Laravel PWA for tracking upcoming earnings and executing trades. 2–10 users, small cloud server (free PaaS).

---

## Stack

| Layer | Choice |
|-------|--------|
| Backend | Laravel 10+ / PHP 8.1+ |
| Database | SQLite (WAL mode) |
| Frontend | Blade + Alpine.js + Tailwind CSS |
| Cache | File-based (no Redis) |
| Auth | Laravel Breeze |
| Market Data | Finnhub paid tier |
| Trading | Alpaca API (Moomoo: future stub only) |
| PWA | Service Worker + Web Manifest |

---

## Architecture

### Patterns to USE
- **Eloquent Models** — direct DB access, no repository layer
- **Service Classes** — lean, single-purpose business logic
- **Provider Interfaces** — the ONE abstraction: swap market data / trading via `.env`
- **DTOs** — typed data between layers
- **API Resources** — consistent JSON responses
- **Form Requests** — input validation
- **Artisan Commands** — scheduled data sync

### Patterns to AVOID (hard rules)
- No Repository Pattern
- No Laravel Queues / Horizon
- No Redis
- No Docker
- No background job classes
- No complex middleware stacks
- No Yahoo Finance scraping (Finnhub paid tier covers everything)

---

## Provider Architecture

```
MarketDataService → MarketDataProviderInterface → FinnhubProvider (active)
                                                → PolygonProvider (future stub)
                                                → MockMarketDataProvider (dev)

TradingService    → TradingProviderInterface   → AlpacaProvider (active)
                                                → MoomooProvider (future stub)
                                                → MockTradingProvider (dev)
```

Switch provider with one `.env` change:
```env
MARKET_DATA_PROVIDER=finnhub   # finnhub | polygon | mock
TRADING_PROVIDER=alpaca        # alpaca | moomoo | mock
USE_MOCK_PROVIDERS=false       # true overrides both above
```

---

## Key Directory Structure

```
app/
├── Console/Commands/
│   ├── UpdateFinancialData.php
│   └── CacheWarmUp.php
├── Contracts/
│   ├── MarketDataProviderInterface.php
│   └── TradingProviderInterface.php
├── DTOs/
│   ├── QuoteDTO.php / EarningsDTO.php / SentimentDTO.php
│   ├── OrderDTO.php / AccountDTO.php / PositionDTO.php
│   └── CompanyProfileDTO.php
├── Http/Controllers/
│   ├── DashboardController.php
│   ├── CompanyController.php
│   └── TradingController.php
├── Models/
│   ├── Company.php          # is_favorite flag lives here
│   ├── Earning.php
│   ├── CompanyFinancial.php
│   ├── Sentiment.php
│   ├── StockPrice.php
│   └── TradingOrder.php
├── Providers/
│   ├── ApiServiceProvider.php
│   ├── MarketData/FinnhubProvider.php
│   ├── MarketData/MockMarketDataProvider.php
│   ├── Trading/AlpacaProvider.php
│   └── Trading/MockTradingProvider.php
└── Services/
    ├── MarketDataService.php
    ├── TradingService.php
    └── SentimentService.php
```

---

## Database — Key Tables

| Table | Purpose |
|-------|---------|
| `companies` | Single registry; `is_favorite` boolean replaces separate watchlist |
| `earnings` | From Finnhub `/calendar/earnings`; FK on `symbol` |
| `company_financials` | Metrics, fair value, revenue estimates |
| `sentiments` | Score (-1 to 1), label, analyst rating, news JSON |
| `stock_prices` | Cached quotes, refreshed every 30s |
| `trading_orders` | Alpaca order history; `alpaca_order_id` field |
| `api_logs` | Debug + rate limit tracking |
| `user_settings` | Per-user date range preference, theme |

All model relationships use `symbol` as FK (not `id`) for direct joins.

---

## Finnhub Endpoints → DB Mapping

| Endpoint | Stored In |
|----------|-----------|
| `/calendar/earnings` | `earnings` |
| `/quote` | `stock_prices` |
| `/stock/profile2` | `companies` |
| `/news-sentiment` | `sentiments` |
| `/stock/recommendation` | `sentiments` |
| `/stock/metric` | `company_financials` |
| `/stock/price-target` | `company_financials.fair_value_estimate` |
| `/stock/revenue-estimate` | `company_financials.revenue_estimate` |

---

## Caching (file driver, no Redis)

| Data | TTL |
|------|-----|
| Stock quote | 30 seconds |
| Earnings calendar | 1 hour |
| Sentiment | 1 hour |
| Financials / price target | 6 hours |
| Company profile | 24 hours |
| Account / positions (Alpaca) | 60 seconds |

Cache keys pattern: `trading_assistant.{type}.{symbol}`
Invalidate account + positions cache immediately after any trade.

---

## Routes Summary

```
GET  /dashboard                          DashboardController@index
GET  /companies                          CompanyController@index
POST /companies                          CompanyController@store
DELETE /companies/{symbol}               CompanyController@destroy
PATCH /companies/{symbol}/favorite       CompanyController@toggleFavorite
POST /orders                             TradingController@place
GET  /orders                             TradingController@history
GET  /api/symbols/search?q=             CompanyController@search
GET  /api/quotes/{symbol}               CompanyController@quote
GET  /api/account                        TradingController@account
```

---

## Artisan Commands

```bash
php artisan financial:update --type=all        # sync all data from Finnhub
php artisan financial:update --type=earnings
php artisan financial:update --type=stocks
php artisan financial:update --type=sentiments
php artisan cache:warm-up

# Cron (twice daily)
0 9  * * * php artisan financial:update --morning
0 18 * * * php artisan financial:update --evening
```

---

## Environment — Required Keys

```env
FINNHUB_API_KEY=           # paid tier required
ALPACA_API_KEY=
ALPACA_SECRET=
ALPACA_PAPER_URL=https://paper-api.alpaca.markets
ALPACA_LIVE_URL=https://api.alpaca.markets
PAPER_TRADING_MODE=true    # always true until explicitly enabled
ENABLE_TRADING=false
```

---

## Trading Safety Rules
- `PAPER_TRADING_MODE=true` and `ENABLE_TRADING=false` are the defaults — never change without explicit instruction
- Always require confirmation before order submission
- Respect `MAX_POSITION_SIZE` and `DAILY_TRADE_LIMIT` env limits
- Log every order attempt to `api_logs` and `trading_orders`

---

## Full Spec
See `BUILD_SPEC.md` for complete SQL schema, wireframes, mock data fixtures, DTO field lists, and user stories.
