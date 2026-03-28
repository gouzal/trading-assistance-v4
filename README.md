# TradingAssistant

A Laravel PWA for tracking upcoming earnings and executing trades. Built for 2–10 users on a small cloud server.

---

## Stack

| Layer | Choice |
| ----- | ------ |
| Backend | Laravel 13 / PHP 8.1+ |
| Database | SQLite (WAL mode) |
| Frontend | Blade + Alpine.js + Tailwind CSS |
| Cache | File-based |
| Auth | Laravel Breeze |
| Market Data | Finnhub (paid tier) |
| Trading | Alpaca API |
| PWA | Service Worker + Web Manifest |

---

## Requirements

- PHP 8.1+
- Composer
- Node.js + npm
- Finnhub paid API key
- Alpaca API key (for trading)

---

## Installation

```bash
# 1. Install dependencies
composer install
npm install && npm run build

# 2. Copy and configure environment
cp .env.example .env
php artisan key:generate

# 3. Run migrations
php artisan migrate

# 4. (Optional) Seed with sample data
php artisan db:seed
```

---

## Environment Variables

```env
# App
APP_ENV=local
APP_URL=http://localhost

# Database (SQLite, no changes needed)
DB_CONNECTION=sqlite

# Market Data
MARKET_DATA_PROVIDER=finnhub       # finnhub | polygon | mock
FINNHUB_API_KEY=your_key_here

# Trading
TRADING_PROVIDER=alpaca            # alpaca | moomoo | mock
ALPACA_API_KEY=your_key_here
ALPACA_SECRET=your_secret_here
ALPACA_PAPER_URL=https://paper-api.alpaca.markets
ALPACA_LIVE_URL=https://api.alpaca.markets
PAPER_TRADING_MODE=true            # keep true until ready for live
ENABLE_TRADING=false               # set true to allow order placement

# Safety limits
MAX_POSITION_SIZE=1000             # max USD per position
DAILY_TRADE_LIMIT=5                # max orders per day

# Provider overrides
USE_MOCK_PROVIDERS=false           # true = ignore above, use mock data
```

> **Windows dev note:** PHP's cURL lacks a CA bundle on Windows. The app automatically disables SSL verification when `APP_ENV=local`. This is safe for local development only.

---

## Artisan Commands

### `financial:update` — Sync data from Finnhub

Only syncs data for companies you have added via the **Companies → Search** tab.

```bash
# Sync everything
php artisan financial:update --type=all

# Sync specific data types
php artisan financial:update --type=earnings    # earnings calendar
php artisan financial:update --type=stocks      # stock prices
php artisan financial:update --type=sentiments  # sentiment + analyst ratings

# Scheduled run shortcuts (used by cron)
php artisan financial:update --morning          # earnings + stocks
php artisan financial:update --evening          # sentiments + financials
```

### `cache:warm-up` — Pre-warm file cache

```bash
php artisan cache:warm-up
```

### Other useful commands

```bash
# Clear all cached data (run after config changes)
php artisan cache:clear

# Clear compiled config (run after .env changes)
php artisan config:clear

# Remove companies not added by user (system-auto-created rows)
php artisan tinker --execute="App\Models\Company::where('is_favorite', false)->delete();"
```

---

## Cron Schedule

The scheduler is configured in `routes/console.php` and runs automatically via Laravel's task scheduler:

```cron
0 9  * * *   php artisan financial:update --morning
0 18 * * *   php artisan financial:update --evening
```

To activate the scheduler on your server, add one cron entry:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Usage

### Adding companies to track

1. Go to **Companies** in the nav
2. Use the **Search** tab to search Finnhub by ticker or name (e.g. `AAPL`, `Tesla`)
3. Click **Add to Watchlist** — the company is now tracked
4. Run `php artisan financial:update --type=all` to pull its data

### Viewing earnings

- Go to **Dashboard** — shows upcoming earnings for your tracked companies
- Filter by 1–4 weeks, toggle **Watchlist only**, sort by sentiment or price

### Placing trades

- Trading is disabled by default (`ENABLE_TRADING=false`)
- Set `ENABLE_TRADING=true` and configure Alpaca keys to enable
- `PAPER_TRADING_MODE=true` uses Alpaca's paper trading endpoint (no real money)

---

## Provider Switching

Swap market data or trading providers with one `.env` change — no code changes needed:

```env
MARKET_DATA_PROVIDER=finnhub   # finnhub | polygon | mock
TRADING_PROVIDER=alpaca        # alpaca | moomoo | mock
USE_MOCK_PROVIDERS=false       # true overrides both above (useful for dev/testing)
```

---

## Routes

| Method | URL | Description |
| ------ | --- | ----------- |
| GET | `/dashboard` | Earnings calendar |
| GET | `/companies` | Company search + watchlist |
| POST | `/companies` | Add company to watchlist |
| DELETE | `/companies/{symbol}` | Remove company |
| PATCH | `/companies/{symbol}/favorite` | Toggle favorite |
| POST | `/orders` | Place a trade order |
| GET | `/orders` | Order history |
| GET | `/api/symbols/search?q=` | Live Finnhub symbol search |
| GET | `/api/quotes/{symbol}` | Real-time quote |
| GET | `/api/account` | Alpaca account + positions |
