# TradingAssistant — Complete Build Specification

**Version**: 3.0 (Unified)
**Target**: Laravel PWA, small cloud server (free PaaS tier or equivalent), 2–10 users
**Data Source**: Finnhub paid tier (all data) + Alpaca trading API (Moomoo: future)

---

## 1. Project Overview

A **Laravel Progressive Web Application (PWA)** for tracking upcoming earnings releases and executing trades.
Small-scale app (2–10 users), deployable on any **small cloud server** — free PaaS tiers (Render, Railway, Fly.io, etc.) are sufficient.

### Design Philosophy
> **Simplicity over complexity.** Every architectural decision prioritizes low-resource performance and maintainability.
> No over-engineering. No patterns that don't earn their place.

### Core User Workflow
1. User opens dashboard → sees upcoming earnings for companies on their watchlist (highlighted) + all other earnings
2. Filters by date range (1–4 weeks) or "Watchlist Only"
3. Reviews each company card: price, sentiment, revenue estimate, fair value
4. Clicks **Buy** or **Sell** → modal opens → selects quantity → confirms order via Alpaca

---

## 2. Technology Stack

| Component | Technology | Rationale |
|-----------|------------|-----------|
| Backend | Laravel 10+ / PHP 8.1+ | Mature, well-documented |
| Database | SQLite (WAL mode) | Zero-config, lightweight, easy backup |
| Frontend | Blade + Alpine.js + Tailwind CSS | Minimal JS, reactive without SPA overhead |
| Cache | File-based | No Redis needed for 2–10 users |
| Auth | Laravel Breeze | Simple, battle-tested |
| Market Data | Finnhub (paid tier) | Single source for all financial data |
| Trading | Alpaca API | Order execution |
| PWA | Service Worker + Web Manifest | Offline support, native-like installation |

---

## 3. Architecture

### 3.1 Directory Structure

```
app/
├── Console/Commands/
│   ├── UpdateFinancialData.php     # Sync market data to DB
│   └── CacheWarmUp.php             # Pre-warm file cache
├── Contracts/                      # Provider interfaces (ONE intentional abstraction)
│   ├── MarketDataProviderInterface.php
│   └── TradingProviderInterface.php
├── DTOs/
│   ├── QuoteDTO.php
│   ├── SentimentDTO.php
│   ├── EarningsDTO.php
│   ├── OrderDTO.php
│   ├── AccountDTO.php
│   ├── PositionDTO.php
│   └── CompanyProfileDTO.php
├── Http/
│   ├── Controllers/                # Thin — delegate to services
│   │   ├── DashboardController.php
│   │   ├── CompanyController.php   # search, add, toggle favorite, delete
│   │   └── TradingController.php
│   ├── Requests/
│   │   ├── WatchlistRequest.php
│   │   └── TradingOrderRequest.php
│   └── Resources/
│       ├── CompanyResource.php
│       └── EarningsResource.php
├── Models/
│   ├── Company.php              # is_favorite flag lives here
│   ├── Earning.php              # belongs to Company (via symbol)
│   ├── CompanyFinancial.php
│   ├── Sentiment.php
│   ├── StockPrice.php
│   ├── TradingOrder.php
│   └── ApiLog.php
├── Providers/
│   ├── ApiServiceProvider.php      # Binds interfaces → implementations
│   ├── MarketData/
│   │   ├── FinnhubProvider.php         # Active provider
│   │   ├── MockMarketDataProvider.php  # Dev/testing
│   │   └── PolygonProvider.php         # Future stub
│   └── Trading/
│       ├── AlpacaProvider.php          # Active provider
│       ├── MockTradingProvider.php     # Dev/testing
│       └── MoomooProvider.php          # Future stub
└── Services/
    ├── MarketDataService.php       # Uses MarketDataProviderInterface
    ├── TradingService.php          # Uses TradingProviderInterface
    └── SentimentService.php

resources/views/
├── layouts/app.blade.php
├── dashboard/
│   ├── index.blade.php
│   └── partials/
│       ├── company-card.blade.php
│       ├── trading-modal.blade.php
│       └── date-range-selector.blade.php
└── watchlist/index.blade.php

public/
├── sw.js                           # Service Worker
└── manifest.json                   # PWA Manifest
```

### 3.2 Patterns to USE

| Pattern | Purpose |
|---------|---------|
| Eloquent Models | Direct DB access — no repository in between |
| Service Classes | Business logic, lean and single-purpose |
| Provider Interfaces | Swap market data / trading backends without touching services |
| DTOs | Clean typed data between layers |
| API Resources | Consistent JSON formatting |
| Form Requests | Input validation |
| Artisan Commands | Scheduled tasks, data operations |

> **Note on Provider Interfaces**: This is the ONE abstraction layer we intentionally keep.
> It allows switching `finnhub → polygon` or `alpaca → moomoo` by changing one `.env` line.
> This is NOT the Repository Pattern (which adds unnecessary DB abstraction).

### 3.3 Patterns to AVOID

| Anti-Pattern | Why |
|--------------|-----|
| ❌ Repository Pattern | Unnecessary for SQLite + small scale |
| ❌ Laravel Queues | Use direct API calls via providers with basic retry |
| ❌ Redis | File cache is sufficient for 2–10 users |
| ❌ Docker | Direct deployment is simpler; PaaS handles containers if needed |
| ❌ Horizon / Telescope | Basic file logging is sufficient |
| ❌ Microservices | Monolith is the right fit here |
| ❌ Complex middleware stacks | Basic Laravel security is sufficient |
| ❌ Yahoo Finance scraping | Finnhub paid tier covers all required data |
| ❌ Background Job system | Direct API calls replace this |

---

## 4. Database Schema

### 4.1 Tables

```sql
-- Single company registry — all companies the app knows about
-- Populated either by: Finnhub earnings calendar sync OR manual user search+add
CREATE TABLE companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    sector VARCHAR(100),
    industry VARCHAR(100),
    country VARCHAR(10),
    logo_url VARCHAR(255),
    is_favorite BOOLEAN DEFAULT FALSE,  -- TRUE = user marked as "favorite / watchlist"
    notes TEXT,                         -- optional user notes
    added_by VARCHAR(20) DEFAULT 'system', -- 'system' (from earnings sync) | 'user' (manual add)
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Earnings calendar entries (from Finnhub /calendar/earnings)
-- One row per company per earnings event
CREATE TABLE earnings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(10) NOT NULL,
    announcement_date DATE NOT NULL,
    announcement_time VARCHAR(10),   -- 'BMO' (before market), 'AMC' (after), 'DMH' (during)
    estimated_revenue DECIMAL(15,2),
    actual_revenue DECIMAL(15,2),
    estimated_eps DECIMAL(10,4),
    actual_eps DECIMAL(10,4),
    api_data JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (symbol) REFERENCES companies(symbol)
);

-- Company financials (Finnhub paid tier)
CREATE TABLE company_financials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(10) NOT NULL UNIQUE,
    company_name VARCHAR(255),
    market_cap DECIMAL(20,2),
    current_price DECIMAL(15,4),
    pe_ratio DECIMAL(10,2),
    pb_ratio DECIMAL(10,2),
    peg_ratio DECIMAL(10,2),
    debt_to_equity DECIMAL(10,2),
    profit_margin DECIMAL(10,4),
    revenue_estimate DECIMAL(15,2),
    revenue_turnover_pct DECIMAL(10,2),
    fair_value_estimate DECIMAL(15,4),
    week_52_high DECIMAL(15,4),
    week_52_low DECIMAL(15,4),
    historic_high DECIMAL(15,4),
    historic_low DECIMAL(15,4),
    data_provider VARCHAR(20) DEFAULT 'finnhub',
    last_updated TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- CRITICAL: Market sentiment (Finnhub /news-sentiment + /stock/recommendation)
CREATE TABLE sentiments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(10) NOT NULL,
    sentiment_score DECIMAL(5,4),        -- Numeric score (-1.0 to 1.0)
    sentiment_label VARCHAR(20),          -- 'Very Good', 'Good', 'Neutral', 'Bad', 'Very Bad'
    analyst_rating VARCHAR(50),           -- 'Strong Buy', 'Buy', 'Hold', 'Sell', 'Strong Sell'
    buy_count INTEGER,
    hold_count INTEGER,
    sell_count INTEGER,
    news_data JSON,                       -- Recent news articles
    revised_expectations BOOLEAN DEFAULT FALSE,
    revision_direction VARCHAR(10),       -- 'up', 'down', null
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Stock prices (cached from Finnhub /quote)
CREATE TABLE stock_prices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(10) NOT NULL UNIQUE,
    current_price DECIMAL(15,4),
    day_open DECIMAL(15,4),
    day_high DECIMAL(15,4),
    day_low DECIMAL(15,4),
    day_change DECIMAL(15,4),
    day_change_percent DECIMAL(10,4),
    volume BIGINT,
    last_updated TIMESTAMP
);

-- API request logs (debugging + rate limit tracking)
CREATE TABLE api_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    provider VARCHAR(20),                 -- 'finnhub', 'alpaca', 'polygon', 'moomoo'
    endpoint VARCHAR(100),
    symbol VARCHAR(10),
    status VARCHAR(20),                   -- 'success', 'failed', 'rate_limited'
    response_time_ms INTEGER,
    error_message TEXT,
    created_at TIMESTAMP
);

-- Trading orders (Alpaca integration)
CREATE TABLE trading_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    order_type VARCHAR(10) NOT NULL,      -- 'buy', 'sell'
    order_class VARCHAR(10) NOT NULL,     -- 'market', 'limit'
    quantity INTEGER NOT NULL,
    limit_price DECIMAL(15,4),
    executed_price DECIMAL(15,4),
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'submitted', 'filled', 'partial', 'cancelled', 'failed'
    alpaca_order_id VARCHAR(100),
    error_message TEXT,
    submitted_at TIMESTAMP,
    executed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Per-user preferences
CREATE TABLE user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    earnings_date_range INTEGER DEFAULT 2,  -- weeks (1–4)
    theme VARCHAR(10) DEFAULT 'dark',
    default_order_type VARCHAR(10) DEFAULT 'market',
    notification_settings JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 4.2 Eloquent Model Relationships

```php
// Company.php
class Company extends Model {
    public function earnings(): HasMany       // one company → many earnings events
    public function financial(): HasOne       // one company → one financials record
    public function sentiment(): HasOne       // one company → one latest sentiment
    public function stockPrice(): HasOne      // one company → one cached price
}

// Earning.php
class Earning extends Model {
    public function company(): BelongsTo     // belongs to Company via symbol
}

// CompanyFinancial.php
class CompanyFinancial extends Model {
    public function company(): BelongsTo
}

// Sentiment.php
class Sentiment extends Model {
    public function company(): BelongsTo
}

// StockPrice.php
class StockPrice extends Model {
    public function company(): BelongsTo
}

// TradingOrder.php
class TradingOrder extends Model {
    public function user(): BelongsTo        // belongs to User
}
```

All relationships use `symbol` as the foreign key (not `id`) to allow direct joins without looking up the company id first.

### 4.3 Data Flow

```
                        ┌─────────────────────────────┐
                        │       companies table        │
                        │  (single source of truth)   │
                        │                             │
                        │  symbol | name | is_favorite│
                        │  AAPL   | Apple | TRUE  ★   │
                        │  MSFT   | Micro | FALSE     │
                        │  NVDA   | Nvidi | TRUE  ★   │
                        └──────────────┬──────────────┘
                                       │
          ┌──────── populated by ──────┴──────── populated by ────────┐
          │                                                            │
          ▼                                                            ▼
Finnhub /calendar/earnings                               User searches Finnhub /search
(auto-adds unknown symbols                               (manual add via search bar
 with is_favorite = FALSE)                                → user can flag as favorite)
          │
          ▼
Finnhub API (for each company in table)
  /quote                 → stock_prices table
  /stock/profile2        → companies (name, sector, logo)
  /news-sentiment        → sentiments table
  /stock/recommendation  → sentiments table
  /stock/metric          → company_financials table
  /stock/price-target    → company_financials.fair_value_estimate
  /stock/revenue-estimate→ company_financials.revenue_estimate
          │
          ▼
     Dashboard
  Shows all companies with upcoming earnings
  ★ Favorites highlighted in gold/amber
  [☆ Favorites Only] toggle filters the list

Alpaca API ──> trading_orders table ──> Order Management UI
```

---

## 5. API Integration

### 5.1 Provider Architecture

```
APPLICATION LAYER
┌──────────────────────┐   ┌──────────────────────┐
│  MarketDataService   │   │   TradingService      │
└──────────┬───────────┘   └──────────┬────────────┘
           │                          │
           ▼                          ▼
┌──────────────────────┐   ┌──────────────────────┐
│MarketDataProvider    │   │TradingProvider       │
│  Interface           │   │  Interface           │
└──────────┬───────────┘   └──────────┬────────────┘
           │                          │
    ┌──────┼──────┐            ┌──────┼──────┐
    ▼      ▼      ▼            ▼      ▼      ▼
Finnhub Polygon  Mock      Alpaca  Moomoo   Mock
(active)(future)(dev)      (active)(future) (dev)
```

### 5.2 MarketDataProviderInterface Methods

| Method | Returns | Finnhub Endpoint |
|--------|---------|-----------------|
| `getQuote(symbol)` | QuoteDTO | `/quote` |
| `getBulkQuotes(symbols[])` | Collection | `/quote` (batched) |
| `getEarningsCalendar(from, to)` | Collection | `/calendar/earnings` |
| `getCompanyProfile(symbol)` | CompanyProfileDTO | `/stock/profile2` |
| `getRecommendations(symbol)` | array | `/stock/recommendation` |
| `getSentiment(symbol)` | SentimentDTO | `/news-sentiment` |
| `getBasicFinancials(symbol)` | array | `/stock/metric` |
| `getPriceTarget(symbol)` | array | `/stock/price-target` |
| `getEarningsEstimates(symbol)` | array | `/stock/eps-estimate` |
| `getRevenueEstimates(symbol)` | array | `/stock/revenue-estimate` |
| `searchSymbol(query)` | array | `/search?q=QUERY` |

### 5.3 TradingProviderInterface Methods

| Method | Returns | Purpose |
|--------|---------|---------|
| `getAccount()` | AccountDTO | Balance, buying power |
| `getPositions()` | Collection | Current holdings |
| `getPosition(symbol)` | PositionDTO | Single position |
| `placeMarketOrder(symbol, side, qty)` | OrderDTO | Execute market order |
| `placeLimitOrder(symbol, side, qty, price)` | OrderDTO | Execute limit order |
| `cancelOrder(orderId)` | bool | Cancel pending order |
| `getOrderStatus(orderId)` | OrderDTO | Check status |
| `getOrderHistory(limit)` | Collection | Past orders |

### 5.4 DTOs

| DTO | Key Fields |
|-----|-----------|
| QuoteDTO | symbol, currentPrice, open, high, low, previousClose, change, changePercent, volume, timestamp, provider |
| EarningsDTO | symbol, companyName, announcementDate, announcementTime, estimatedEps, actualEps, estimatedRevenue, actualRevenue |
| SentimentDTO | symbol, score (-1 to 1), label, bullishCount, bearishCount, newsArticles, revisedExpectations, timestamp |
| OrderDTO | providerId, symbol, side, type, quantity, limitPrice, filledPrice, status, errorMessage, createdAt |
| AccountDTO | cash, buyingPower, portfolioValue, equity, currency, isPaperAccount |
| PositionDTO | symbol, quantity, averageCost, currentPrice, marketValue, unrealizedPL, unrealizedPLPercent |
| CompanyProfileDTO | symbol, name, industry, sector, country, marketCap, sharesOutstanding, logo, weburl, ipo |

### 5.5 Sentiment Score Mapping

| Score Range | Label |
|-------------|-------|
| >= 0.6 | Very Good |
| >= 0.3 | Good |
| >= -0.3 | Neutral |
| >= -0.6 | Bad |
| < -0.6 | Very Bad |

### 5.6 Mock Providers (Dev/Testing)

**MockMarketDataProvider** behavior:
- `getQuote(symbol)` → realistic price with ±5% random variance, deterministic by symbol
- `getEarningsCalendar(from, to)` → 10–20 companies spread across date range
- `getSentiment(symbol)` → random score between -0.8 and 0.8
- Pre-configured for: AAPL, MSFT, GOOGL, AMZN, META, NVDA, TSLA, JPM, BAC, etc.

**MockTradingProvider** behavior:
- Account with $100,000 buying power
- 3–5 sample positions
- Market orders fill instantly at mock price
- Limit orders simulate fill if price matches
- Positions and balance update after each trade

### 5.7 Provider Selection

```env
# Switch provider by changing one line
MARKET_DATA_PROVIDER=finnhub   # options: finnhub | polygon | mock
TRADING_PROVIDER=alpaca        # options: alpaca | moomoo | mock
USE_MOCK_PROVIDERS=false       # overrides both above when true
```

| Environment | Market Data | Trading |
|-------------|-------------|---------|
| local | mock | mock |
| testing | mock | mock |
| staging | finnhub | mock |
| production | finnhub | alpaca |

---

## 6. Core Features

### 6.1 Dashboard — Single List with Favorite Flag

There is **one list of companies**. No separate watchlist table.

Each company in the `companies` table has an `is_favorite` boolean.
- `is_favorite = TRUE` → displayed with a gold ★ icon and amber border
- `is_favorite = FALSE` → displayed normally

**How companies enter the list:**
1. **Auto** — Finnhub earnings sync adds any company with an upcoming earnings event (unflagged by default)
2. **Manual** — User searches by symbol, finds a company not yet in the DB, and adds it (optionally flagging as favorite at that moment)

**Dashboard behavior:**
- Shows all companies that have earnings in the selected date range
- Favorites are visually distinct (gold/amber) but in the same list
- **[★ Favorites Only]** toggle hides non-favorite companies
- Default range: 2 weeks ahead (UI: 1–4 weeks, persisted per user)

### 6.2 Company Card

```
┌─────────────────────────────────────────┐
│ [★] AAPL                    📅 Dec 12  │  ← ★ filled = favorite (click to toggle)
│ Apple Inc.          Technology          │    ☆ empty  = not favorite
├─────────────────────────────────────────┤
│ Price: $185.42        ▲ +1.23 (0.67%)  │
│ Est. Revenue: $94.5B  Fair Value: $200 │
│ Sentiment: ██████░░░░ Good             │  ← Visual bar + label
│ ⬆️ Revised Up                          │
├─────────────────────────────────────────┤
│      [Buy]              [Sell]         │
└─────────────────────────────────────────┘
```

The **★/☆ star icon** in the card header is a one-click toggle:
- Click ☆ → sets `is_favorite = TRUE`, card border turns gold, no page reload
- Click ★ → sets `is_favorite = FALSE`, gold border removed
- Backend: `PATCH /companies/{symbol}/favorite` toggles the flag

**Card Data Sources:**

| Field | Finnhub Endpoint |
|-------|-----------------|
| Company Name | `/stock/profile2` |
| Ticker / Earnings Date | `/calendar/earnings` |
| Current Price + Change | `/quote` |
| Estimated Revenue | `/stock/revenue-estimate` |
| Revenue Turnover % | `/stock/metric` |
| Fair Value Estimate | `/stock/price-target` |
| Sentiment Label | `/news-sentiment` |
| Revised Expectations | `/stock/eps-estimate` (change detection) |
| Analyst Rating | `/stock/recommendation` |

### 6.3 Trading Modal

Triggered by Buy/Sell button on any company card.

```
┌─────────────────────────────────────────────┐
│  [BUY / SELL] AAPL                    [X]  │
├─────────────────────────────────────────────┤
│  Current Price: $185.42                     │
│  Last Updated: 2 minutes ago               │
├─────────────────────────────────────────────┤
│  Quantity: [_______] shares                 │
│  Quick Select: [1/2] [1/4] [1/25] [All]    │
├─────────────────────────────────────────────┤
│  Order Type: (●) Market  ( ) Limit          │
│  Limit Price: [_______]  (if limit selected)│
├─────────────────────────────────────────────┤
│  Estimated Total: $1,854.20                 │
├─────────────────────────────────────────────┤
│        [Cancel]        [Confirm Order]      │
└─────────────────────────────────────────────┘
```

**Quick Quantity Buttons**:
- **1/2** — half of buying power (buy) or half of position (sell)
- **1/4** — quarter
- **1/25** — 4% of portfolio (small position)
- **All** — full buying power or entire position

**Safety requirements**:
- Confirmation step before submission
- Paper trading mode (no real orders)
- Position size limits (configurable via `MAX_POSITION_SIZE`)
- Daily trade limit (configurable via `DAILY_TRADE_LIMIT`)

### 6.4 Date Range Selector

- Location: Dashboard header, prominent
- Options: 1 week / 2 weeks (default) / 3 weeks / 4 weeks
- AJAX filter (no page reload)
- Selection persisted to `user_settings.earnings_date_range`

### 6.5 Watchlist Management

#### Symbol Search Bar (Add to Watchlist)

A live search input available on both the **watchlist page** and as a **quick-add widget on the dashboard header**.

**Behavior**:
- User types a ticker symbol (e.g. `AAP`) → results appear below as a dropdown, no page reload
- Each result shows: **ticker symbol**, **company name**, **sector** (from Finnhub `/stock/profile2`)
- Clicking a result adds the company to the watchlist instantly and closes the dropdown
- If the symbol is already on the watchlist, the result shows a "✓ Already watching" state (non-clickable)
- Minimum 1 character to trigger search; debounced at 300ms

**UI Wireframe**:
```
┌──────────────────────────────────────────┐
│ 🔍 Search by symbol...          [+ Add] │
└──────────────────────────────────────────┘
         ↓ (typing "APP")
┌──────────────────────────────────────────┐
│  AAPL   Apple Inc.          Technology  │  ← click to add
│  APPH   AppHarvest Inc.     Agriculture │
│  APPN   Appian Corporation  Technology  │
└──────────────────────────────────────────┘
```

**Implementation**:
- Frontend: Alpine.js component with `x-on:input` debounce → calls `/api/symbols/search?q=QUERY`
- Backend: `GET /api/symbols/search` → hits Finnhub `/search?q=QUERY`
  - Returns only symbols **not already in the `companies` table** (already-known ones show inline "Already in list ★/☆")
- Selecting a result → modal with two options:
  ```
  Add "NVDA — Nvidia" to your list?
  [ ] Mark as favorite ★
  [Cancel]   [Add Company]
  ```
- `POST /companies` — inserts into `companies` table with `added_by = 'user'`
- If "Mark as favorite" checked → `is_favorite = TRUE`
- Results capped at 8 items in dropdown

#### Removing a Company
- Companies added by the system (`added_by = 'system'`) are **auto-removed** when they no longer have upcoming earnings in the sync window
- Companies added manually by the user (`added_by = 'user'`) are **kept permanently** until the user explicitly deletes them from the Companies list page
- Delete = hard delete from `companies` table (cascades to financials, sentiments, stock prices)

---

## 7. UI/UX Requirements

### 7.1 Design System

**Theme**: Dark mode default

| Purpose | Color | Hex |
|---------|-------|-----|
| Background (primary) | Near Black | `#0f0f0f` |
| Background (card) | Dark Gray | `#1a1a1a` |
| Text (primary) | White | `#ffffff` |
| Text (secondary) | Gray | `#9ca3af` |
| Accent | Deep Blue | `#2563eb` |
| Gain / Positive | Green | `#10b981` |
| Loss / Negative | Red | `#ef4444` |
| Favorite Indicator (★) | Gold/Amber | `#f59e0b` |

**Typography**: Inter or Roboto
**Icons**: Heroicons or Lucide

### 7.2 Responsive Layout

| Device | Breakpoint | Layout |
|--------|------------|--------|
| Mobile | < 640px | Single column, large touch targets |
| Tablet | 640–1024px | 2-column grid |
| Desktop | > 1024px | 3–4 column grid, hover states |

### 7.3 Dashboard Header

```
┌────────────────────────────────────────────────────────┐
│ 📈 TradingAssistant          [👤 User]  [⚙️ Settings] │
├────────────────────────────────────────────────────────┤
│ Earnings: [1w] [2w ✓] [3w] [4w]   [★ Favorites Only] │
└────────────────────────────────────────────────────────┘
```

### 7.4 Performance Targets

| Metric | Target |
|--------|--------|
| Initial Load | < 3 seconds on free-tier server |
| API Response | < 1 second (cached) |
| Time to Interactive | < 2 seconds |
| Offline | Full capability via Service Worker |

---

## 8. PWA Implementation

### 8.1 Service Worker Caching Strategy

| Asset Type | Strategy |
|------------|----------|
| Static assets (CSS/JS/fonts) | Cache-First |
| API data (earnings, quotes) | Network-First |
| Company profiles / financials | Stale-While-Revalidate |

### 8.2 Web App Manifest

| Property | Value |
|----------|-------|
| name | TradingAssistant |
| short_name | Trading |
| start_url | /dashboard |
| display | standalone |
| background_color | #0f0f0f |
| theme_color | #2563eb |
| icons | 192×192, 512×512 PNG |

---

## 9. HTTP Routes

### routes/web.php
```php
// Auth (Laravel Breeze)
require __DIR__.'/auth.php';

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Company management
Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
Route::delete('/companies/{symbol}', [CompanyController::class, 'destroy'])->name('companies.destroy');
Route::patch('/companies/{symbol}/favorite', [CompanyController::class, 'toggleFavorite'])->name('companies.favorite');

// Trading
Route::post('/orders', [TradingController::class, 'place'])->name('orders.place');
Route::get('/orders', [TradingController::class, 'history'])->name('orders.history');

// User settings
Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
```

### routes/api.php
```php
// Symbol search (hits Finnhub /search, returns companies not yet in local DB)
Route::get('/symbols/search', [CompanyController::class, 'search']);

// Live price refresh for a symbol (called by trading modal)
Route::get('/quotes/{symbol}', [CompanyController::class, 'quote']);

// Account info for quantity calculation in trading modal
Route::get('/account', [TradingController::class, 'account']);
```

### Controller Method Summary

| Controller | Method | Route | Purpose |
|-----------|--------|-------|---------|
| DashboardController | `index` | GET /dashboard | Load companies with upcoming earnings + financials + sentiment |
| CompanyController | `index` | GET /companies | List all companies in DB (manage page) |
| CompanyController | `search` | GET /api/symbols/search?q= | Search Finnhub for symbols not yet in DB |
| CompanyController | `store` | POST /companies | Add company to DB (from search result), optionally flag as favorite |
| CompanyController | `toggleFavorite` | PATCH /companies/{symbol}/favorite | Flip `is_favorite` boolean |
| CompanyController | `destroy` | DELETE /companies/{symbol} | Remove manually-added company |
| CompanyController | `quote` | GET /api/quotes/{symbol} | Return fresh price from provider (for trading modal) |
| TradingController | `place` | POST /orders | Submit buy/sell order via TradingService |
| TradingController | `history` | GET /orders | List trading_orders for current user |
| TradingController | `account` | GET /api/account | Return AccountDTO (buying power, positions) |

---

## 10. Environment Configuration

```env
# Application
APP_NAME=TradingAssistant
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

# Database (lightweight, no external DB server needed)
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database/database.sqlite

# Cache / Sessions (no Redis needed)
CACHE_STORE=file
SESSION_DRIVER=file
CACHE_PREFIX=trading_assistant

# ===========================================
# PROVIDER SELECTION
# ===========================================
MARKET_DATA_PROVIDER=finnhub     # finnhub | polygon | mock
TRADING_PROVIDER=alpaca          # alpaca | moomoo | mock
USE_MOCK_PROVIDERS=false         # true = override both above with mock

# ===========================================
# FINNHUB (Paid Tier)
# ===========================================
FINNHUB_API_KEY=your_paid_key_here
FINNHUB_BASE_URL=https://finnhub.io/api/v1
FINNHUB_TIMEOUT=10
FINNHUB_RETRIES=3

# ===========================================
# POLYGON.IO (Future Alternative)
# ===========================================
# POLYGON_API_KEY=
# POLYGON_BASE_URL=https://api.polygon.io

# ===========================================
# ALPACA TRADING (Active)
# ===========================================
ALPACA_API_KEY=your_alpaca_key
ALPACA_SECRET=your_alpaca_secret
ALPACA_PAPER_URL=https://paper-api.alpaca.markets
ALPACA_LIVE_URL=https://api.alpaca.markets

# ===========================================
# MOOMOO (Future Alternative)
# ===========================================
# MOOMOO_API_KEY=
# MOOMOO_SECRET=
# MOOMOO_OPEND_HOST=127.0.0.1
# MOOMOO_OPEND_PORT=11111

# ===========================================
# FEATURE FLAGS
# ===========================================
ENABLE_TRADING=false
PAPER_TRADING_MODE=true

# ===========================================
# TRADING SAFETY LIMITS
# ===========================================
MAX_POSITION_SIZE=10000
DAILY_TRADE_LIMIT=50
REQUIRE_CONFIRMATION=true
```

### Config Files

**config/services.php** additions:
```php
'market_data' => ['provider' => env('MARKET_DATA_PROVIDER', 'finnhub')],

'finnhub' => [
    'api_key'  => env('FINNHUB_API_KEY'),
    'base_url' => env('FINNHUB_BASE_URL', 'https://finnhub.io/api/v1'),
    'timeout'  => env('FINNHUB_TIMEOUT', 10),
    'retries'  => env('FINNHUB_RETRIES', 3),
],

'trading' => ['provider' => env('TRADING_PROVIDER', 'alpaca')],

'alpaca' => [
    'api_key'   => env('ALPACA_API_KEY'),
    'secret'    => env('ALPACA_SECRET'),
    'paper_url' => env('ALPACA_PAPER_URL', 'https://paper-api.alpaca.markets'),
    'live_url'  => env('ALPACA_LIVE_URL', 'https://api.alpaca.markets'),
],
```

**config/trading.php**:
```php
return [
    'paper_mode' => env('PAPER_TRADING_MODE', true),
    'enabled'    => env('ENABLE_TRADING', false),
    'limits' => [
        'max_position_size' => env('MAX_POSITION_SIZE', 10000),
        'daily_trade_limit' => env('DAILY_TRADE_LIMIT', 50),
    ],
    'safety' => [
        'require_confirmation' => env('REQUIRE_CONFIRMATION', true),
    ],
];
```

---

## 11. Caching Strategy

All caching uses the file driver (no Redis). Keys are prefixed with `trading_assistant`.

| Data Type | Cache Key Pattern | TTL | Reason |
|-----------|------------------|-----|--------|
| Stock price (quote) | `quote.{symbol}` | 30 seconds | Near real-time for trading modal |
| Earnings calendar | `earnings.{from}.{to}` | 1 hour | Changes rarely intraday |
| Company profile | `profile.{symbol}` | 24 hours | Static information |
| Basic financials | `financials.{symbol}` | 6 hours | Updates once or twice daily |
| Sentiment | `sentiment.{symbol}` | 1 hour | News cycles |
| Price target | `pricetarget.{symbol}` | 6 hours | Analyst updates are infrequent |
| Account (Alpaca) | `account.{userId}` | 60 seconds | Balance changes after trades |
| Positions (Alpaca) | `positions.{userId}` | 60 seconds | Position changes after trades |

Cache is invalidated explicitly after a trade is placed (account + positions keys for that user).

---

## 12. Error Handling & Logging

### Logging Channels (file-based)
- `earnings` — earnings data updates
- `api` — external API calls (success/failure/rate limits)
- `errors` — application errors

### Error Scenarios

| Scenario | Handling |
|----------|---------|
| API failure | Show cached data with stale indicator |
| Rate limited | Retry with exponential backoff, log warning |
| No cache + API down | Offline PWA mode with cached static data |
| DB error | Graceful error page |
| Invalid input | Form Request validation, user-friendly messages |
| Trade rejected | Show rejection reason, log to api_logs |

---

## 13. Implementation Phases

### Phase 1 — Core Infrastructure (Week 1)

1. **Provider Abstraction Layer** ← foundation for everything
   - `MarketDataProviderInterface` + `TradingProviderInterface`
   - All DTOs (QuoteDTO, SentimentDTO, OrderDTO, AccountDTO, PositionDTO, CompanyProfileDTO)
   - `ApiServiceProvider` binding interfaces to implementations
   - `MockMarketDataProvider` and `MockTradingProvider`

2. **FinnhubProvider** — all 10 endpoints implemented

3. **Database migrations** — all tables from Section 4.1

4. **Market Sentiment feature** ← critical missing piece
   - `sentiments` migration
   - `SentimentService` using provider interface
   - Sentiment display on company cards with visual bar

5. **Architecture cleanup**
   - Delete `app/Repositories/` (if exists)
   - Remove background job classes
   - Simplify middleware stack

### Phase 2 — Trading (Week 2)

6. **AlpacaProvider** — account, positions, orders (paper mode first)
7. **TradingService** — business logic using TradingProviderInterface
8. **TradingController** — REST endpoints for modal
9. **Trading modal** — Blade + Alpine.js component with quantity presets

### Phase 3 — UI & Features (Week 3)

10. **Dashboard** — single list, favorites highlighted, [★ Favorites Only] toggle
11. **Date range selector** — AJAX filter, persisted preference
12. **Dark mode theme** — default Tailwind configuration
13. **Watchlist page** — add/remove/manage companies
14. **Mobile responsive** — touch-friendly targets

### Phase 4 — Deployment (Week 4)

15. **Deployment script** — git pull + migrate + cache warm (works on any PaaS)
16. **Performance tuning** — SQLite WAL mode, query indexes, asset minification
17. **Provider stubs** — PolygonProvider and AlpacaProvider documented skeletons
18. **Cron setup** — `php artisan financial:update` twice daily

---

## 14. Mock Data Fixtures

When `USE_MOCK_PROVIDERS=true`, the app uses these fixtures instead of real API calls. Seeders should populate the DB with this data for local development.

### Pre-loaded Companies (DatabaseSeeder)

```php
// 10 companies pre-seeded for development
$companies = [
    ['symbol' => 'AAPL',  'name' => 'Apple Inc.',            'sector' => 'Technology',      'is_favorite' => true],
    ['symbol' => 'MSFT',  'name' => 'Microsoft Corporation', 'sector' => 'Technology',      'is_favorite' => true],
    ['symbol' => 'GOOGL', 'name' => 'Alphabet Inc.',         'sector' => 'Technology',      'is_favorite' => false],
    ['symbol' => 'AMZN',  'name' => 'Amazon.com Inc.',       'sector' => 'Consumer Cyclical','is_favorite' => false],
    ['symbol' => 'META',  'name' => 'Meta Platforms Inc.',   'sector' => 'Technology',      'is_favorite' => true],
    ['symbol' => 'NVDA',  'name' => 'Nvidia Corporation',    'sector' => 'Technology',      'is_favorite' => true],
    ['symbol' => 'TSLA',  'name' => 'Tesla Inc.',            'sector' => 'Consumer Cyclical','is_favorite' => false],
    ['symbol' => 'JPM',   'name' => 'JPMorgan Chase & Co.',  'sector' => 'Financial',       'is_favorite' => false],
    ['symbol' => 'BAC',   'name' => 'Bank of America Corp.', 'sector' => 'Financial',       'is_favorite' => false],
    ['symbol' => 'AVGO',  'name' => 'Broadcom Inc.',         'sector' => 'Technology',      'is_favorite' => true],
];
```

### Mock Earnings (spread across next 3 weeks from seeder run date)

```php
// Earnings seeded relative to Carbon::now()
$earnings = [
    ['symbol' => 'AAPL',  'days_from_now' => 5,  'time' => 'BMO', 'est_eps' => 2.10, 'est_revenue' => 94500000000],
    ['symbol' => 'MSFT',  'days_from_now' => 6,  'time' => 'AMC', 'est_eps' => 2.93, 'est_revenue' => 60200000000],
    ['symbol' => 'NVDA',  'days_from_now' => 7,  'time' => 'AMC', 'est_eps' => 4.60, 'est_revenue' => 32800000000],
    ['symbol' => 'META',  'days_from_now' => 8,  'time' => 'AMC', 'est_eps' => 4.71, 'est_revenue' => 39100000000],
    ['symbol' => 'AVGO',  'days_from_now' => 10, 'time' => 'AMC', 'est_eps' => 1.38, 'est_revenue' => 14200000000],
    ['symbol' => 'GOOGL', 'days_from_now' => 12, 'time' => 'AMC', 'est_eps' => 1.85, 'est_revenue' => 85300000000],
    ['symbol' => 'AMZN',  'days_from_now' => 13, 'time' => 'AMC', 'est_eps' => 0.84, 'est_revenue' => 141700000000],
    ['symbol' => 'TSLA',  'days_from_now' => 15, 'time' => 'AMC', 'est_eps' => 0.60, 'est_revenue' => 25500000000],
    ['symbol' => 'JPM',   'days_from_now' => 16, 'time' => 'BMO', 'est_eps' => 4.30, 'est_revenue' => 41200000000],
    ['symbol' => 'BAC',   'days_from_now' => 17, 'time' => 'BMO', 'est_eps' => 0.77, 'est_revenue' => 25100000000],
];
```

### Mock Stock Prices (deterministic by symbol)

```php
$prices = [
    'AAPL'  => ['price' => 185.42, 'change' => +1.23, 'change_pct' => +0.67],
    'MSFT'  => ['price' => 412.10, 'change' => +4.94, 'change_pct' => +1.21],
    'GOOGL' => ['price' => 175.30, 'change' => -0.85, 'change_pct' => -0.48],
    'AMZN'  => ['price' => 198.50, 'change' => +2.10, 'change_pct' => +1.07],
    'META'  => ['price' => 521.80, 'change' => +4.70, 'change_pct' => +0.91],
    'NVDA'  => ['price' => 495.00, 'change' => -1.50, 'change_pct' => -0.30],
    'TSLA'  => ['price' =>  245.60, 'change' => -3.20, 'change_pct' => -1.29],
    'JPM'   => ['price' => 198.70, 'change' => +0.95, 'change_pct' => +0.48],
    'BAC'   => ['price' =>  38.45, 'change' => +0.15, 'change_pct' => +0.39],
    'AVGO'  => ['price' => 187.30, 'change' => +2.05, 'change_pct' => +1.11],
];
```

### Mock Sentiments (deterministic by symbol)

```php
$sentiments = [
    'AAPL'  => ['score' =>  0.42, 'label' => 'Good',      'revised' => false, 'direction' => null],
    'MSFT'  => ['score' =>  0.65, 'label' => 'Very Good', 'revised' => true,  'direction' => 'up'],
    'GOOGL' => ['score' =>  0.10, 'label' => 'Neutral',   'revised' => false, 'direction' => null],
    'AMZN'  => ['score' =>  0.35, 'label' => 'Good',      'revised' => true,  'direction' => 'up'],
    'META'  => ['score' =>  0.55, 'label' => 'Good',      'revised' => false, 'direction' => null],
    'NVDA'  => ['score' =>  0.72, 'label' => 'Very Good', 'revised' => true,  'direction' => 'up'],
    'TSLA'  => ['score' => -0.20, 'label' => 'Neutral',   'revised' => true,  'direction' => 'down'],
    'JPM'   => ['score' =>  0.30, 'label' => 'Good',      'revised' => false, 'direction' => null],
    'BAC'   => ['score' =>  0.05, 'label' => 'Neutral',   'revised' => false, 'direction' => null],
    'AVGO'  => ['score' =>  0.68, 'label' => 'Very Good', 'revised' => true,  'direction' => 'up'],
];
```

### Mock Account (MockTradingProvider)

```php
AccountDTO:
  cash             = 25000.00
  buyingPower      = 25000.00
  portfolioValue   = 37450.00
  equity           = 62450.00
  currency         = 'USD'
  isPaperAccount   = true

Positions (3 pre-loaded):
  AAPL  → qty: 10, avgCost: 180.00, currentPrice: 185.42
  MSFT  → qty:  5, avgCost: 400.00, currentPrice: 412.10
  NVDA  → qty:  8, avgCost: 480.00, currentPrice: 495.00
```

---

## 15. Artisan Commands

```bash
# Sync financial data from Finnhub
php artisan financial:update --type=all
php artisan financial:update --type=earnings
php artisan financial:update --type=stocks
php artisan financial:update --type=sentiments

# Cache management
php artisan cache:warm-up
php artisan cache:warm-up --clear

# Database
php artisan migrate:fresh --seed --force

# Cron (add to system crontab)
0 9  * * * cd /path/to/app && php artisan financial:update --morning
0 18 * * * cd /path/to/app && php artisan financial:update --evening
```

---

## 16. Architecture Alignment Checklist

### Remove (over-engineered)
- [ ] `app/Repositories/` directory
- [ ] Repository interface files
- [ ] Background job classes (replaced by direct provider calls)
- [ ] Complex security middleware (`SecurityHeaders`, `RateLimitApi`, `AuditService`)
- [ ] Redis configuration references

### Keep (appropriate complexity)
- [x] `app/Services/` — lean, focused service classes
- [x] `app/DTOs/` — clean data structures
- [x] `app/Http/Resources/` — JSON response formatting
- [x] `app/Http/Requests/` — input validation
- [x] Eloquent models with relationships
- [x] File-based cache and sessions

### Add (new architecture)
- [ ] `app/Contracts/MarketDataProviderInterface.php`
- [ ] `app/Contracts/TradingProviderInterface.php`
- [ ] `app/Providers/ApiServiceProvider.php`
- [ ] `app/Providers/MarketData/FinnhubProvider.php`
- [ ] `app/Providers/MarketData/MockMarketDataProvider.php`
- [ ] `app/Providers/Trading/AlpacaProvider.php`
- [ ] `app/Providers/Trading/MockTradingProvider.php`
- [ ] All DTOs in `app/DTOs/`
- [ ] `app/Models/Company.php` (with `is_favorite` flag — replaces old WatchlistCompany)
- [ ] `app/Models/Sentiment.php`
- [ ] `app/Services/MarketDataService.php`
- [ ] `app/Services/TradingService.php`
- [ ] `companies` migration (with `is_favorite`, `added_by` columns)
- [ ] `sentiments` migration
- [ ] `config/trading.php`
- [ ] Provider config in `config/services.php`

---

## 17. Success Criteria

### Must Have (MVP)
- [ ] Dashboard loads < 3 seconds on a free-tier cloud server
- [ ] Offline mode displays cached data (Service Worker)
- [ ] Single company list with is_favorite flag — favorites highlighted in gold/amber
- [ ] Market sentiment displayed with labels (Very Good → Very Bad)
- [ ] Watchlist companies highlighted (gold/amber) in earnings list
- [ ] Date range selector functional (1–4 weeks, AJAX, persisted)
- [ ] PWA installable on mobile
- [ ] Provider abstraction layer implemented
- [ ] FinnhubProvider covering all required endpoints
- [ ] AlpacaProvider functional in paper trading mode
- [ ] MockMarketDataProvider and MockTradingProvider for dev/testing

### Should Have
- [ ] Live trading modal with quantity presets (1/2, 1/4, 1/25, All)
- [ ] Live Alpaca order execution
- [ ] Dark mode UI fully polished
- [ ] Order history page

### Nice to Have
- [ ] PolygonProvider stub (ready for future migration)
- [ ] MoomooProvider stub (ready for future migration)
- [ ] Push notifications for earnings alerts
- [ ] Portfolio performance tracking over time

---

## 18. Scalability Path

| Users | Architecture |
|-------|-------------|
| 2–10 (current) | SQLite + file cache — free PaaS tier sufficient (this spec) |
| 10–50 | Add Redis, switch to MySQL/PostgreSQL |
| 50–100 | Laravel Queues for background sync |
| 100+ | Consider microservices, CDN |

---

## 19. Security Essentials

- Environment variables for all secrets and API keys
- CSRF protection on all forms
- Input validation via Form Requests
- XSS protection via Blade escaping
- HTTPS required for PWA
- API keys never logged or exposed in responses
- Trading confirmation required before order submission
- Paper trading mode default (`PAPER_TRADING_MODE=true`, `ENABLE_TRADING=false`)

---

## 20. Complete User Story — Example Walkthrough

> **Persona**: Karim, a retail investor who follows tech earnings closely.
> He uses the app on his phone during his lunch break and on his laptop in the evening.
> He has an Alpaca brokerage account with ~$25,000 in buying power.

---

### Monday Morning — Setting Up for the Week

Karim opens his phone, taps the TradingAssistant icon on his home screen (installed as a PWA).
The app loads in under 2 seconds from the service worker cache.

He sees the dashboard showing earnings for the **next 2 weeks**.
The list has 14 companies — none are starred yet because he just set up the app.

```
Dashboard — Earnings next 2 weeks
─────────────────────────────────────────────────────────
☆ AAPL    📅 Dec 12 BMO   $185.42 ▲ +0.67%   Neutral
☆ MSFT    📅 Dec 13 AMC   $412.10 ▲ +1.20%   Good
☆ NVDA    📅 Dec 14 AMC   $495.00 ▼ -0.30%   Very Good
☆ META    📅 Dec 15 AMC   $521.80 ▲ +0.90%   Good
...11 more
```

---

### Step 1 — Karim Builds His Favorites

He already follows AAPL, MSFT, and NVDA closely.
He clicks ☆ on the AAPL card → it turns gold ★, card gets an amber border.
He does the same for MSFT and NVDA.

Three cards are now highlighted in gold. The rest remain normal.

---

### Step 2 — He Discovers a Company Not in the List

Karim remembers reading about **Broadcom (AVGO)** reporting earnings this week but doesn't see it in the list.
He uses the search bar in the dashboard header:

```
🔍 AVGO
─────────────────────────────────────────
  AVGO   Broadcom Inc.   Semiconductors    ← not in list yet
```

He clicks the result. A small modal appears:

```
┌──────────────────────────────────────────┐
│  Add Broadcom Inc. (AVGO) to your list? │
│                                          │
│  [★] Mark as favorite                   │
│                                          │
│       [Cancel]       [Add Company]       │
└──────────────────────────────────────────┘
```

He checks **Mark as favorite** and clicks **Add Company**.

AVGO is now in the `companies` table (`added_by = 'user'`, `is_favorite = TRUE`).
It immediately appears on the dashboard with a gold ★ and its Finnhub data populates within seconds.

```
★ AVGO    📅 Dec 11 AMC   $187.30 ▲ +1.10%   Very Good  ⬆️ Revised Up
```

---

### Step 3 — Focus Mode

Karim now has 4 favorites out of 15 companies.
He clicks **[★ Favorites Only]** in the dashboard header.

The list collapses to just his 4 starred companies:

```
Dashboard — Favorites Only — Earnings next 2 weeks
──────────────────────────────────────────────────────────────
★ AVGO    📅 Dec 11 AMC   $187.30 ▲ +1.10%   Very Good  ⬆️
★ AAPL    📅 Dec 12 BMO   $185.42 ▲ +0.67%   Neutral
★ MSFT    📅 Dec 13 AMC   $412.10 ▲ +1.20%   Good
★ NVDA    📅 Dec 14 AMC   $495.00 ▼ -0.30%   Very Good
```

He changes the date range to **3 weeks** to check if anything interesting is coming further out.
The list updates instantly — two more favorites have earnings in week 3, they appear below.

---

### Step 4 — Spotting the Opportunity

Karim focuses on **AVGO**:
- Sentiment: **Very Good**
- ⬆️ Revised Up — analysts raised EPS estimates this week
- Current price: $187.30
- Fair Value estimate: $210
- Earnings tomorrow after market close (AMC)

He decides this is worth a position before the earnings release.

---

### Step 5 — Opening the Trade

He taps **[Buy]** on the AVGO card.
The trading modal slides up:

```
┌─────────────────────────────────────────────────┐
│  BUY  AVGO — Broadcom Inc.                [X]  │
├─────────────────────────────────────────────────┤
│  Current Price:  $187.30                        │
│  Last Updated:   18 seconds ago                 │
├─────────────────────────────────────────────────┤
│  Quantity: [  ] shares                          │
│  Quick:  [1/2]  [1/4]  [1/25]  [All]           │
├─────────────────────────────────────────────────┤
│  Order Type:  (●) Market   ( ) Limit            │
├─────────────────────────────────────────────────┤
│  Estimated Total:  —                            │
├─────────────────────────────────────────────────┤
│       [Cancel]        [Confirm Order]           │
└─────────────────────────────────────────────────┘
```

His Alpaca account has **$25,000 buying power**.

He clicks **[1/4]** — the app calculates:
- 25% of $25,000 = $6,250
- $6,250 ÷ $187.30 = **33 shares**

Quantity auto-fills to **33**. Estimated total shows **$6,180.90**.

He keeps **Market** order (he wants to enter before close) and taps **[Confirm Order]**.

```
┌──────────────────────────────────────────────┐
│  Confirm Order                               │
│                                              │
│  BUY 33 shares of AVGO at market price      │
│  Estimated total: ~$6,180.90                │
│                                              │
│       [Go Back]        [Place Order]        │
└──────────────────────────────────────────────┘
```

He taps **[Place Order]**.

---

### Step 6 — Order Confirmed

The app sends the order to Alpaca via `TradingService → AlpacaProvider`.
The modal closes. A toast notification appears at the top:

```
✓  Order submitted — 33 shares AVGO @ market
   Alpaca order ID: a1b2c3d4-e5f6-7890-abcd-ef1234567890
```

The order is logged in `trading_orders` with `status = 'submitted'`.

---

### Step 7 — Evening Check (After Earnings)

That evening, Karim opens the app again (this time on his laptop).
AVGO has reported — earnings beat estimates by 12%.

The AVGO card now shows:
```
★ AVGO    📅 Dec 11 AMC   $201.40 ▲ +7.5%   Very Good  ⬆️ Revised Up
```

Price moved from $187.30 to $201.40 — his 33 shares gained **~$467**.

He decides to take partial profit. He taps **[Sell]** on the AVGO card.
In the modal he clicks **[1/2]** — the app sees his position of 33 shares and fills in **16 shares**.

He places a **Limit** order at **$202.00** to squeeze a few more cents.

```
SELL 16 shares AVGO @ limit $202.00
Estimated total: ~$3,232.00
```

Order submitted. Dashboard remains live — he watches for the fill notification.

---

### What Happened Behind the Scenes

| User Action | System Response |
|-------------|----------------|
| Tapped ★ on AAPL/MSFT/NVDA | `PATCH /companies/{symbol}/favorite` — `is_favorite = TRUE` |
| Searched AVGO | `GET /api/symbols/search?q=AVGO` → Finnhub `/search` |
| Added AVGO as favorite | `POST /companies` → inserted with `is_favorite = TRUE, added_by = 'user'` |
| Clicked [★ Favorites Only] | Dashboard query: `WHERE is_favorite = 1 AND earnings.date BETWEEN now AND +3 weeks` |
| Clicked [Buy] on AVGO | Fetched live price from `stock_prices` (cached ≤30s), loaded Alpaca buying power |
| Clicked [1/4] | `floor(buyingPower * 0.25 / currentPrice)` = 33 shares |
| Confirmed order | `TradingService::placeMarketOrder('AVGO', 'buy', 33)` → Alpaca API → logged to `trading_orders` |
| Clicked [Sell] → [1/2] | Fetched current position from Alpaca → `floor(33 / 2)` = 16 shares |
