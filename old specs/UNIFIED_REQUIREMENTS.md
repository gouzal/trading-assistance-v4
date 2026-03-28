# TradingAssistant-v2 - Unified Requirements Specification

**Version**: 2.0  
**Last Updated**: December 2025  
**Document Type**: Single Source of Truth

---

## 1. Executive Summary

### 1.1 Project Overview
A **Laravel Progressive Web Application (PWA)** for tracking upcoming earnings releases and executing trades. Designed for **2-10 users** and optimized for **Raspberry Pi 4** deployment.

### 1.2 Key Objectives
- Display upcoming earnings with financial data from multiple sources
- Provide market sentiment analysis for informed trading decisions
- Enable trading via Moomoo API integration
- Deliver a fast, offline-capable PWA experience on Pi hardware

### 1.3 Design Philosophy
**Simplicity over complexity.** This is a small-scale application. Every architectural decision should prioritize Pi performance and maintainability over enterprise patterns.

---

## 2. Technical Architecture

### 2.1 Technology Stack

| Component | Technology | Rationale |
|-----------|------------|-----------|
| **Backend** | Laravel 10+ / PHP 8.1+ | Mature framework, excellent documentation |
| **Database** | SQLite (WAL mode) | Zero-config, Pi-friendly, easy backup |
| **Frontend** | Blade + Alpine.js + Tailwind CSS | Minimal JS footprint, reactive without SPA complexity |
| **Cache** | File-based | No Redis dependency, sufficient for 2-10 users |
| **Authentication** | Laravel Breeze | Simple, battle-tested |
| **APIs** | Finnhub (paid tier) + Moomoo | Provider abstraction layers for flexibility |

### 2.2 Directory Structure

```
app/
├── Console/Commands/           # Artisan commands for data updates
│   ├── UpdateFinancialData.php
│   └── CacheWarmUp.php
├── Contracts/                  # Provider interfaces (abstraction layer)
│   ├── MarketDataProviderInterface.php
│   └── TradingProviderInterface.php
├── DTOs/                       # Data Transfer Objects
│   ├── FinancialDataDTO.php
│   ├── QuoteDTO.php
│   ├── EarningsDTO.php
│   ├── SentimentDTO.php
│   └── OrderDTO.php
├── Http/
│   ├── Controllers/            # Thin controllers, delegate to services
│   │   ├── DashboardController.php
│   │   ├── WatchlistController.php
│   │   └── TradingController.php
│   ├── Requests/               # Form validation
│   │   ├── WatchlistRequest.php
│   │   └── TradingOrderRequest.php
│   └── Resources/              # API response formatting
│       ├── CompanyResource.php
│       └── EarningsResource.php
├── Models/                     # Eloquent models with relationships
│   ├── WatchlistCompany.php
│   ├── Earning.php
│   ├── CompanyFinancial.php
│   ├── Sentiment.php
│   ├── StockPrice.php
│   ├── TradingOrder.php
│   └── ApiLog.php
├── Providers/                  # Provider implementations
│   ├── MarketData/
│   │   ├── FinnhubProvider.php       # Current provider
│   │   ├── PolygonProvider.php       # Alternative (future)
│   │   └── MockMarketDataProvider.php # Testing/development
│   └── Trading/
│       ├── MoomooProvider.php        # Current provider
│       ├── AlpacaProvider.php        # Alternative (future)
│       └── MockTradingProvider.php   # Testing/development
└── Services/                   # Business logic (lean, focused)
    ├── MarketDataService.php         # Uses MarketDataProviderInterface
    ├── TradingService.php            # Uses TradingProviderInterface
    └── SentimentService.php

resources/
├── views/
│   ├── layouts/app.blade.php
│   ├── dashboard/
│   │   ├── index.blade.php
│   │   └── partials/
│   │       ├── company-card.blade.php
│   │       ├── trading-modal.blade.php
│   │       └── date-range-selector.blade.php
│   └── watchlist/
│       └── index.blade.php
├── css/app.css
└── js/app.js

public/
├── sw.js                       # Service Worker
└── manifest.json               # PWA Manifest
```

### 2.3 Patterns to USE

| Pattern | Purpose |
|---------|---------|
| **Eloquent Models** | Database interactions with relationships |
| **Service Classes** | Business logic separation (lean, single-purpose) |
| **Provider Interfaces** | Abstraction for external APIs (market data, trading) |
| **DTOs** | Clean data structures between layers |
| **API Resources** | Consistent JSON response formatting |
| **Form Requests** | Input validation |
| **Artisan Commands** | Scheduled tasks, data operations |

> **Note on Provider Interfaces**: This is the ONE abstraction layer we intentionally keep. It allows switching between market data providers (Finnhub → Polygon.io) or trading providers (Moomoo → Alpaca) without changing business logic. This is NOT the same as the Repository Pattern (which abstracts database access unnecessarily).

### 2.4 Patterns to AVOID

| Anti-Pattern | Reason |
|--------------|--------|
| ❌ Repository Pattern | Unnecessary abstraction for SQLite/small scale |
| ❌ Laravel Queues | Over-engineered; use direct API calls with retry |
| ❌ Redis | Overkill for 2-10 users; file cache sufficient |
| ❌ Docker | Direct Pi deployment is simpler |
| ❌ Horizon/Telescope | Basic logging is sufficient |
| ❌ Microservices | Monolith is perfect for this scale |
| ❌ Complex middleware stacks | Basic Laravel security is sufficient |

---

## 3. Database Schema

### 3.1 Complete Table Definitions

```sql
-- Core watchlist (manually managed)
CREATE TABLE watchlist_companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    notes TEXT,
    priority INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Earnings calendar (Finnhub API)
CREATE TABLE earnings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(10) NOT NULL,
    company_name VARCHAR(255),
    announcement_date DATE NOT NULL,
    announcement_time VARCHAR(10),  -- 'BMO', 'AMC', 'DMH'
    estimated_revenue DECIMAL(15,2),
    actual_revenue DECIMAL(15,2),
    estimated_eps DECIMAL(10,4),
    actual_eps DECIMAL(10,4),
    api_data JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_earnings_date (announcement_date),
    INDEX idx_earnings_symbol (symbol)
);

-- Company financial data (from Finnhub paid tier)
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
    updated_at TIMESTAMP,
    INDEX idx_financials_symbol (symbol)
);

-- CRITICAL: Market sentiment analysis
CREATE TABLE sentiments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(10) NOT NULL,
    sentiment_score DECIMAL(5,4),       -- Numeric score from API
    sentiment_label VARCHAR(20),         -- 'Very Good', 'Good', 'Neutral', 'Bad', 'Very Bad'
    analyst_rating VARCHAR(50),          -- 'Strong Buy', 'Buy', 'Hold', 'Sell', 'Strong Sell'
    buy_count INTEGER,
    hold_count INTEGER,
    sell_count INTEGER,
    news_data JSON,                      -- Recent news articles
    revised_expectations BOOLEAN DEFAULT FALSE,
    revision_direction VARCHAR(10),      -- 'up', 'down', null
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_sentiment_symbol (symbol)
);

-- Stock prices (cached from Finnhub)
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
    last_updated TIMESTAMP,
    INDEX idx_price_symbol (symbol)
);

-- API request logs (debugging and rate limit tracking)
CREATE TABLE api_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    provider VARCHAR(20),                -- 'finnhub', 'moomoo', 'polygon', 'alpaca'
    endpoint VARCHAR(100),
    symbol VARCHAR(10),
    status VARCHAR(20),                  -- 'success', 'failed', 'rate_limited'
    response_time_ms INTEGER,
    error_message TEXT,
    created_at TIMESTAMP
);

-- Trading orders (Moomoo integration)
CREATE TABLE trading_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    order_type VARCHAR(10) NOT NULL,     -- 'buy', 'sell'
    order_class VARCHAR(10) NOT NULL,    -- 'market', 'limit'
    quantity INTEGER NOT NULL,
    limit_price DECIMAL(15,4),
    executed_price DECIMAL(15,4),
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'submitted', 'filled', 'partial', 'cancelled', 'failed'
    moomoo_order_id VARCHAR(100),
    error_message TEXT,
    submitted_at TIMESTAMP,
    executed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_symbol (symbol),
    INDEX idx_orders_status (status)
);

-- User preferences
CREATE TABLE user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    earnings_date_range INTEGER DEFAULT 2,  -- weeks (1-4)
    theme VARCHAR(10) DEFAULT 'dark',
    default_order_type VARCHAR(10) DEFAULT 'market',
    notification_settings JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 3.2 Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                    PROVIDER ABSTRACTION LAYER                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌─────────────────────┐         ┌─────────────────────┐           │
│  │ MarketDataProvider  │         │  TradingProvider    │           │
│  │    Interface        │         │    Interface        │           │
│  └──────────┬──────────┘         └──────────┬──────────┘           │
│             │                               │                       │
│      ┌──────┴──────┐                 ┌──────┴──────┐               │
│      ▼             ▼                 ▼             ▼               │
│  ┌────────┐   ┌────────┐        ┌────────┐   ┌────────┐           │
│  │Finnhub │   │Polygon │        │ Moomoo │   │ Alpaca │           │
│  │Provider│   │Provider│        │Provider│   │Provider│           │
│  │(active)│   │(future)│        │(active)│   │(future)│           │
│  └────┬───┘   └────────┘        └────┬───┘   └────────┘           │
│       │                              │                             │
└───────┼──────────────────────────────┼─────────────────────────────┘
        │                              │
        ▼                              ▼
   ┌─────────┐                  ┌──────────┐
   │earnings │                  │trading_  │
   │sentiments│                 │orders    │
   │stock_   │                  └──────────┘
   │prices   │
   │company_ │
   │financials│
   └────┬────┘
        │
        └──────────────────┐
                           ▼
    ┌────────┐       ┌─────────┐
    │watchlist│      │Dashboard│
    │_companies│◄───►│ Display │
    └────────┘       └─────────┘
    (visual indicators)
```

---

## 4. Core Features

### 4.1 Two-List Dashboard Structure

#### Watchlist (Manual, Persistent)
- User-curated list of companies to monitor
- Stored in `watchlist_companies` table
- Supports notes, priority ranking, active/inactive status
- Visual indicators (gold/amber) when appearing in earnings list

#### Earnings List (Dynamic, API-Driven)
- Upcoming earnings from Finnhub `/calendar/earnings`
- Default: 2 weeks ahead (UI adjustable: 1-4 weeks)
- Shows all earnings with watchlist companies highlighted
- Filterable: "Show Watchlist Only" toggle

### 4.2 Company Card Display

Each card must show:

| Field | Source | Finnhub Endpoint |
|-------|--------|------------------|
| Company Name | Finnhub | `/stock/profile2` |
| Ticker Symbol | Finnhub | `/calendar/earnings` |
| Earnings Date | Finnhub | `/calendar/earnings` |
| Current Price | Finnhub | `/quote` |
| Estimated Revenue | Finnhub | `/stock/revenue-estimate` |
| Revenue Turnover % | Finnhub | `/stock/metric` |
| Fair Value Estimate | Finnhub | `/stock/price-target` |
| **Sentiment Label** | Finnhub | `/news-sentiment` |
| **Revised Expectations** | Finnhub | `/stock/eps-estimate` (changes) |
| Watchlist Indicator | Local DB | N/A |
| Buy/Sell Buttons | N/A | N/A |

> **Note**: All data comes from Finnhub paid tier. No Yahoo Finance scraping required.

#### Sentiment Labels
Map numeric sentiment scores to human-readable labels:
- **Very Good**: score >= 0.6
- **Good**: score >= 0.3
- **Neutral**: score >= -0.3
- **Bad**: score >= -0.6
- **Very Bad**: score < -0.6

### 4.3 Trading Modal

**Trigger**: Click Buy or Sell button on company card

**Modal Contents**:
```
┌─────────────────────────────────────────────┐
│  [BUY / SELL] AAPL                    [X]  │
├─────────────────────────────────────────────┤
│  Current Price: $185.42                     │
│  Last Updated: 2 minutes ago                │
├─────────────────────────────────────────────┤
│  Quantity: [_______] shares                 │
│                                             │
│  Quick Select:                              │
│  [1/2] [1/4] [1/25] [All]                   │
├─────────────────────────────────────────────┤
│  Order Type: (●) Market  ( ) Limit          │
│  Limit Price: [_______] (if limit)          │
├─────────────────────────────────────────────┤
│  Estimated Total: $1,854.20                 │
├─────────────────────────────────────────────┤
│        [Cancel]        [Confirm Order]      │
└─────────────────────────────────────────────┘
```

**Quick Quantity Buttons**:
- **1/2**: Half of available buying power or position
- **1/4**: Quarter of available buying power or position
- **1/25**: 4% of portfolio (small position)
- **All**: Full buying power or entire position

### 4.4 Date Range Selector

**Location**: Top of dashboard, prominent placement

**Options**: 1 week, 2 weeks (default), 3 weeks, 4 weeks

**Implementation**: 
- Dropdown or slider
- AJAX filter (no page reload)
- Persisted to user preferences

---

## 5. API Integration (Provider Abstraction Layer)

### 5.1 Provider Architecture Overview

The application uses an **abstraction layer** for external API providers, allowing seamless switching between providers without changing business logic.

```
┌─────────────────────────────────────────────────────────────────┐
│                     APPLICATION LAYER                           │
│  ┌─────────────────────┐    ┌─────────────────────┐            │
│  │  MarketDataService  │    │   TradingService    │            │
│  └──────────┬──────────┘    └──────────┬──────────┘            │
│             │                          │                        │
│             ▼                          ▼                        │
│  ┌─────────────────────┐    ┌─────────────────────┐            │
│  │MarketDataProvider   │    │ TradingProvider     │            │
│  │    Interface        │    │    Interface        │            │
│  └──────────┬──────────┘    └──────────┬──────────┘            │
└─────────────┼───────────────────────────┼───────────────────────┘
              │                           │
   ┌──────────┼──────────┐     ┌──────────┼──────────┐
   │          │          │     │          │          │
   ▼          ▼          ▼     ▼          ▼          ▼
┌──────┐  ┌───────┐  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐
│Finnhub│ │Polygon│  │ MOCK │ │Moomoo│ │Alpaca│ │ MOCK │
│  ✓   │ │(future)│  │  ✓   │ │  ✓   │ │(future)│ │  ✓   │
└──────┘  └───────┘  └──────┘ └──────┘ └──────┘ └──────┘
                        │                          │
                        └─── For Testing/Dev ──────┘
```

### 5.2 Market Data Provider Interface

The `MarketDataProviderInterface` defines the contract for all market data providers:

| Method | Returns | Purpose |
|--------|---------|---------|
| `getName()` | string | Provider identifier ('finnhub', 'polygon') |
| `isAvailable()` | bool | Check if provider is configured |
| `getQuote(symbol)` | QuoteDTO | Real-time stock quote |
| `getBulkQuotes(symbols)` | Collection | Multiple quotes at once |
| `getEarningsCalendar(from, to)` | Collection | Upcoming earnings |
| `getCompanyProfile(symbol)` | CompanyProfileDTO | Company information |
| `getRecommendations(symbol)` | array | Analyst recommendations |
| `getSentiment(symbol)` | SentimentDTO | News sentiment analysis |
| `getBasicFinancials(symbol)` | array | P/E, margins, ratios |
| `getPriceTarget(symbol)` | array | Analyst price targets |
| `getEarningsEstimates(symbol)` | array | EPS estimates |
| `getRevenueEstimates(symbol)` | array | Revenue estimates |

### 5.3 Trading Provider Interface

The `TradingProviderInterface` defines the contract for all trading providers:

| Method | Returns | Purpose |
|--------|---------|---------|
| `getName()` | string | Provider identifier ('moomoo', 'alpaca') |
| `isAvailable()` | bool | Check if provider is configured |
| `isPaperMode()` | bool | Check if paper trading mode |
| `getAccount()` | AccountDTO | Account balance, buying power |
| `getPositions()` | Collection | Current holdings |
| `getPosition(symbol)` | PositionDTO | Specific position |
| `placeMarketOrder(symbol, side, qty)` | OrderDTO | Execute market order |
| `placeLimitOrder(symbol, side, qty, price)` | OrderDTO | Execute limit order |
| `cancelOrder(orderId)` | bool | Cancel pending order |
| `getOrderStatus(orderId)` | OrderDTO | Check order status |
| `getOrderHistory(limit)` | Collection | Past orders |

### 5.4 Data Transfer Objects (DTOs)

| DTO | Fields | Purpose |
|-----|--------|---------|
| **QuoteDTO** | symbol, currentPrice, open, high, low, previousClose, change, changePercent, volume, timestamp, provider | Stock price data |
| **SentimentDTO** | symbol, score (-1 to 1), label, bullishCount, bearishCount, buzz, newsArticles, timestamp, provider | Sentiment analysis |
| **OrderDTO** | providerId, symbol, side, type, quantity, limitPrice, filledPrice, filledQuantity, status, errorMessage, createdAt, filledAt, provider | Order information |
| **AccountDTO** | cash, buyingPower, portfolioValue, equity, currency, isPaperAccount, provider | Account summary |
| **PositionDTO** | symbol, quantity, averageCost, currentPrice, marketValue, unrealizedPL, unrealizedPLPercent, provider | Position details |
| **CompanyProfileDTO** | symbol, name, industry, sector, country, marketCap, sharesOutstanding, logo, weburl, ipo, provider | Company info |

### 5.5 Sentiment Score Mapping

| Score Range | Label |
|-------------|-------|
| >= 0.6 | Very Good |
| >= 0.3 | Good |
| >= -0.3 | Neutral |
| >= -0.6 | Bad |
| < -0.6 | Very Bad |

### 5.6 Finnhub Paid Tier - Available Endpoints

With the **paid tier**, all these endpoints are available:

| Endpoint | Purpose | Rate Limit |
|----------|---------|------------|
| `/quote` | Real-time prices | ✅ Higher limits |
| `/calendar/earnings` | Earnings calendar | ✅ Full access |
| `/stock/profile2` | Company profiles | ✅ Full access |
| `/stock/recommendation` | Analyst recommendations | ✅ Full access |
| `/news-sentiment` | Sentiment analysis | ✅ Full access |
| `/stock/metric` | Basic financials (P/E, margins, etc.) | ✅ Full access |
| `/stock/price-target` | Analyst price targets | ✅ Full access |
| `/stock/eps-estimate` | EPS estimates | ✅ Full access |
| `/stock/revenue-estimate` | Revenue estimates | ✅ Full access |
| `/stock/earnings` | Historical earnings | ✅ Full access |
| `/stock/financials-reported` | Reported financials | ✅ Full access |

**No Yahoo Finance scraping needed** - Finnhub paid tier provides all required data points.

### 5.7 Mock Providers (Testing & Development)

Mock providers implement the same interfaces as real providers but return predefined test data. Essential for:
- **Development** without consuming API quotas
- **Testing** with predictable, consistent data
- **Offline development** when APIs are unavailable
- **CI/CD pipelines** for automated testing

#### MockMarketDataProvider

| Method | Mock Behavior |
|--------|---------------|
| `getQuote(symbol)` | Returns realistic quote with randomized price within ±5% of base |
| `getEarningsCalendar(from, to)` | Returns 10-20 sample companies with earnings in date range |
| `getCompanyProfile(symbol)` | Returns complete profile for known symbols (AAPL, MSFT, GOOGL, etc.) |
| `getSentiment(symbol)` | Returns randomized sentiment score between -0.8 and 0.8 |
| `getBasicFinancials(symbol)` | Returns realistic P/E, P/B, margins based on sector |
| `getRecommendations(symbol)` | Returns mix of buy/hold/sell recommendations |

**Mock Data Characteristics:**
- Deterministic by symbol (same symbol always returns same base data)
- Prices include small random variance for realism
- Earnings dates spread across the requested date range
- All major tech/finance stocks pre-configured (AAPL, MSFT, GOOGL, AMZN, META, NVDA, TSLA, JPM, BAC, etc.)

#### MockTradingProvider

| Method | Mock Behavior |
|--------|---------------|
| `getAccount()` | Returns account with $100,000 buying power |
| `getPositions()` | Returns 3-5 sample positions |
| `placeMarketOrder()` | Always succeeds, returns filled order with current mock price |
| `placeLimitOrder()` | Returns pending order, simulates fill after delay |
| `cancelOrder()` | Always succeeds for pending orders |
| `getOrderHistory()` | Returns 10-20 sample historical orders |

**Mock Trading Behavior:**
- Orders always succeed (no rejections)
- Market orders fill instantly at mock price
- Limit orders fill if price is at or better than limit
- Positions update automatically after order fills
- Account balance adjusts based on trades

#### Provider Selection Configuration

```env
# ===========================================
# PROVIDER SELECTION
# ===========================================

# Market Data Provider
# Options: finnhub, polygon, mock
MARKET_DATA_PROVIDER=finnhub

# Trading Provider  
# Options: moomoo, alpaca, mock
TRADING_PROVIDER=moomoo

# Force mock providers (overrides above settings)
# Useful for development/testing environments
USE_MOCK_PROVIDERS=false
```

#### Environment-Based Provider Selection

| Environment | Market Data | Trading | Notes |
|-------------|-------------|---------|-------|
| `local` | mock | mock | No API calls during development |
| `testing` | mock | mock | Consistent data for automated tests |
| `staging` | finnhub | mock | Real market data, simulated trading |
| `production` | finnhub | moomoo | Full live integration |

---

## 6. UI/UX Requirements

### 6.1 Design System

**Theme**: Dark mode default (professional, eye-friendly)

**Color Palette**:
| Purpose | Color | Hex |
|---------|-------|-----|
| Background (primary) | Near Black | `#0f0f0f` |
| Background (card) | Dark Gray | `#1a1a1a` |
| Text (primary) | White | `#ffffff` |
| Text (secondary) | Gray | `#9ca3af` |
| Accent (primary) | Deep Blue | `#2563eb` |
| Gain/Positive | Green | `#10b981` |
| Loss/Negative | Red | `#ef4444` |
| Watchlist Indicator | Gold/Amber | `#f59e0b` |
| Warning | Orange | `#f97316` |

**Typography**: Inter or Roboto (clean sans-serif)

**Icons**: Heroicons or Lucide (consistent minimalist style)

### 6.2 Responsive Breakpoints

| Device | Breakpoint | Layout |
|--------|------------|--------|
| Mobile | < 640px | Single column, large touch targets |
| Tablet | 640-1024px | 2-column grid |
| Desktop | > 1024px | 3-4 column grid, hover states |

### 6.3 Key UI Components

**Dashboard Header**:
```
┌────────────────────────────────────────────────────────┐
│ 📈 TradingAssistant          [👤 User]  [⚙️ Settings] │
├────────────────────────────────────────────────────────┤
│ Earnings: [1w ▼ 2w ▼ 3w ▼ 4w]     [☆ Watchlist Only] │
└────────────────────────────────────────────────────────┘
```

**Company Card**:
```
┌─────────────────────────────────────────┐
│ ★ AAPL                      📅 Dec 12  │ ← Gold star = watchlisted
│ Apple Inc.                             │
├─────────────────────────────────────────┤
│ Price: $185.42        ▲ +1.23 (0.67%)  │
│ Est. Revenue: $94.5B  Fair Value: $200 │
│ Sentiment: ██████░░░░ Good             │ ← Visual bar + label
│ ⬆️ Revised Up                          │
├─────────────────────────────────────────┤
│      [Buy]              [Sell]         │
└─────────────────────────────────────────┘
```

### 6.4 Performance Requirements

| Metric | Target | Measurement |
|--------|--------|-------------|
| Initial Load | < 3 seconds | Pi hardware |
| API Response | < 1 second | Cached endpoints |
| Time to Interactive | < 2 seconds | After paint |
| Offline Capability | Full | Service worker |

---

## 7. PWA Implementation

### 7.1 Service Worker Strategy

| Asset Type | Strategy | Example |
|------------|----------|---------|
| Static assets | Cache-First | CSS, JS, images, fonts |
| API data | Network-First | Earnings list, quotes |
| Financial data | Stale-While-Revalidate | Company profiles, financials |

**Cached Static Assets**:
- `/` (homepage)
- `/css/app.css`
- `/js/app.js`
- `/offline.html`

### 7.2 Web App Manifest Configuration

| Property | Value |
|----------|-------|
| name | TradingAssistant |
| short_name | Trading |
| start_url | /dashboard |
| display | standalone |
| background_color | #0f0f0f |
| theme_color | #2563eb |
| icons | 192x192, 512x512 PNG |

---

## 8. Environment Configuration

### 8.1 Required Environment Variables

```env
# Application
APP_NAME=TradingAssistant
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

# Database (Pi-optimized)
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

# Cache (Pi-optimized)
CACHE_STORE=file
SESSION_DRIVER=file

# ===========================================
# PROVIDER CONFIGURATION (Abstraction Layer)
# ===========================================

# Market Data Provider Selection
# Options: finnhub, polygon (future)
MARKET_DATA_PROVIDER=finnhub

# Trading Provider Selection
# Options: moomoo, alpaca (future)
TRADING_PROVIDER=moomoo

# ===========================================
# FINNHUB CONFIGURATION (Paid Tier)
# ===========================================
FINNHUB_API_KEY=your_finnhub_paid_key_here
FINNHUB_BASE_URL=https://finnhub.io/api/v1
FINNHUB_TIMEOUT=10
FINNHUB_RETRIES=3

# ===========================================
# POLYGON.IO CONFIGURATION (Future Alternative)
# ===========================================
# POLYGON_API_KEY=
# POLYGON_BASE_URL=https://api.polygon.io

# ===========================================
# MOOMOO TRADING CONFIGURATION
# ===========================================
MOOMOO_API_KEY=your_moomoo_key_here
MOOMOO_SECRET=your_moomoo_secret_here
MOOMOO_PAPER_URL=https://paper-api.moomoo.com
MOOMOO_LIVE_URL=https://api.moomoo.com

# ===========================================
# ALPACA TRADING CONFIGURATION (Future Alternative)
# ===========================================
# ALPACA_API_KEY=
# ALPACA_SECRET=
# ALPACA_PAPER_URL=https://paper-api.alpaca.markets
# ALPACA_LIVE_URL=https://api.alpaca.markets

# ===========================================
# FEATURE FLAGS
# ===========================================
USE_MOCK_DATA=false
ENABLE_TRADING=false
PAPER_TRADING_MODE=true

# ===========================================
# TRADING SAFETY LIMITS
# ===========================================
MAX_POSITION_SIZE=10000
DAILY_TRADE_LIMIT=50
REQUIRE_CONFIRMATION=true
```

### 8.2 Config Files for Providers

**config/services.php** (additions):
```php
return [
    // ... other services
    
    'market_data' => [
        'provider' => env('MARKET_DATA_PROVIDER', 'finnhub'),
    ],
    
    'finnhub' => [
        'api_key' => env('FINNHUB_API_KEY'),
        'base_url' => env('FINNHUB_BASE_URL', 'https://finnhub.io/api/v1'),
        'timeout' => env('FINNHUB_TIMEOUT', 10),
        'retries' => env('FINNHUB_RETRIES', 3),
    ],
    
    'polygon' => [
        'api_key' => env('POLYGON_API_KEY'),
        'base_url' => env('POLYGON_BASE_URL', 'https://api.polygon.io'),
    ],
    
    'trading' => [
        'provider' => env('TRADING_PROVIDER', 'moomoo'),
    ],
    
    'moomoo' => [
        'api_key' => env('MOOMOO_API_KEY'),
        'secret' => env('MOOMOO_SECRET'),
        'paper_url' => env('MOOMOO_PAPER_URL'),
        'live_url' => env('MOOMOO_LIVE_URL'),
    ],
    
    'alpaca' => [
        'api_key' => env('ALPACA_API_KEY'),
        'secret' => env('ALPACA_SECRET'),
        'paper_url' => env('ALPACA_PAPER_URL'),
        'live_url' => env('ALPACA_LIVE_URL'),
    ],
];
```

**config/trading.php**:
```php
return [
    'paper_mode' => env('PAPER_TRADING_MODE', true),
    'enabled' => env('ENABLE_TRADING', false),
    
    'limits' => [
        'max_position_size' => env('MAX_POSITION_SIZE', 10000),
        'daily_trade_limit' => env('DAILY_TRADE_LIMIT', 50),
    ],
    
    'safety' => [
        'require_confirmation' => env('REQUIRE_CONFIRMATION', true),
    ],
];
```

### 8.3 Switching Providers

**To switch from Finnhub to Polygon.io:**
```env
# Change this single line
MARKET_DATA_PROVIDER=polygon
POLYGON_API_KEY=your_polygon_key

# Finnhub key can remain (for fallback or gradual migration)
FINNHUB_API_KEY=your_finnhub_key
```

**To switch from Moomoo to Alpaca:**
```env
# Change this single line
TRADING_PROVIDER=alpaca
ALPACA_API_KEY=your_alpaca_key
ALPACA_SECRET=your_alpaca_secret
```

### 8.4 Mock Data Toggle

When `USE_MOCK_DATA=true`:
- Provider interfaces return mock data
- No external API requests made
- Useful for development and testing
- Mock data matches actual DTO structures

---

## 9. Implementation Priorities

### Phase 1: Core Infrastructure (Week 1)

1. **Implement Provider Abstraction Layer** ← FOUNDATION
   - Create `MarketDataProviderInterface`
   - Create `TradingProviderInterface`
   - Create all DTOs (QuoteDTO, SentimentDTO, OrderDTO, etc.)
   - Register providers in `ApiServiceProvider`

2. **Implement FinnhubProvider (Paid Tier)**
   - All endpoints: `/quote`, `/calendar/earnings`, `/stock/profile2`
   - `/stock/recommendation`, `/news-sentiment`, `/stock/metric`
   - `/stock/price-target`, `/stock/eps-estimate`, `/stock/revenue-estimate`

3. **Implement Market Sentiment** ← CRITICAL FEATURE
   - Create `sentiments` migration
   - Add sentiment display on company cards
   - Map scores to labels: "Very Good" → "Very Bad"

4. **Remove Over-Engineering**
   - Delete `app/Repositories/` directory
   - Refactor controllers to use services with provider interfaces
   - Simplify middleware stack

### Phase 2: Trading & Services (Week 2)

5. **Implement MoomooProvider**
   - Account, positions, orders endpoints
   - Paper trading mode support
   - Order placement and tracking

6. **Create MarketDataService & TradingService**
   - Business logic using provider interfaces
   - Local database sync functionality
   - Error handling and logging

7. **Trading Modal**
   - Create modal Blade component
   - Implement quantity calculation (1/2, 1/4, 1/25, All)
   - Wire up to TradingService

### Phase 3: UI & Features (Week 3)

8. **Date Range Selector**
   - Add UI component (1-4 weeks)
   - Implement AJAX filtering
   - Persist user preference

9. **UI/UX Enhancements**
   - Dark mode theme (default)
   - Watchlist visual indicators (gold/amber)
   - Mobile responsive optimization

10. **Mock Data System**
    - Create mock providers implementing interfaces
    - Environment toggle for mock vs live

### Phase 4: Deployment & Future Providers (Week 4)

11. **Pi Deployment**
    - Deployment script
    - Performance tuning
    - Monitoring setup

12. **Prepare Alternative Providers (Stubs)**
    - Create `PolygonProvider` stub (future)
    - Create `AlpacaProvider` stub (future)
    - Document provider switching process

---

## 10. Success Criteria

### Must Have (MVP)
- [ ] Dashboard loads in < 3 seconds on Pi
- [ ] Offline mode displays cached data
- [ ] Two-list structure (watchlist + earnings)
- [ ] **Market sentiment displayed** (Very Good → Very Bad)
- [ ] Watchlist companies highlighted with visual indicator
- [ ] Date range selector (1-4 weeks)
- [ ] PWA installable on mobile
- [ ] **Provider abstraction layer implemented**
- [ ] **FinnhubProvider fully functional (all endpoints)**
- [ ] **MoomooProvider functional (paper mode)**
- [ ] **MockMarketDataProvider for testing**
- [ ] **MockTradingProvider for testing**

### Should Have
- [ ] Trading modal with quantity presets
- [ ] Live Moomoo trading integration
- [ ] Dark mode UI polished
- [ ] Order history tracking

### Nice to Have
- [ ] PolygonProvider stub ready for future
- [ ] AlpacaProvider stub ready for future
- [ ] Push notifications for earnings alerts
- [ ] Portfolio performance tracking

---

## 11. Architecture Alignment Checklist

### Remove These (Over-Engineered)
- [ ] `app/Repositories/` directory and all repository classes
- [ ] Repository interfaces in old `Contracts/` location
- [ ] Background job classes (use direct API calls via providers)
- [ ] Complex security middleware (basic Laravel sufficient)
- [ ] `AuditService` (use simple logging)
- [ ] Redis configuration references
- [ ] Yahoo Finance scraping (Finnhub paid tier covers all data)

### Keep These (Appropriate Complexity)
- [x] `app/Services/` with lean service classes
- [x] `app/DTOs/` for data transfer objects
- [x] `app/Http/Resources/` for API formatting
- [x] `app/Http/Requests/` for validation
- [x] Eloquent models with relationships
- [x] File-based cache and sessions

### Add These (New Architecture)
- [ ] `app/Contracts/MarketDataProviderInterface.php`
- [ ] `app/Contracts/TradingProviderInterface.php`
- [ ] `app/Providers/MarketData/FinnhubProvider.php`
- [ ] `app/Providers/MarketData/MockMarketDataProvider.php`
- [ ] `app/Providers/Trading/MoomooProvider.php`
- [ ] `app/Providers/Trading/MockTradingProvider.php`
- [ ] `app/Providers/ApiServiceProvider.php`
- [ ] `app/DTOs/QuoteDTO.php`
- [ ] `app/DTOs/SentimentDTO.php`
- [ ] `app/DTOs/OrderDTO.php`
- [ ] `app/DTOs/AccountDTO.php`
- [ ] `app/DTOs/PositionDTO.php`
- [ ] `app/DTOs/CompanyProfileDTO.php`
- [ ] `app/Services/MarketDataService.php`
- [ ] `app/Services/TradingService.php`
- [ ] `app/Models/Sentiment.php`
- [ ] `sentiments` database migration
- [ ] Provider configuration in `config/services.php`
- [ ] Trading configuration in `config/trading.php`
- [ ] Mock data fixtures in `database/fixtures/`

---

## 12. Quick Reference

### Artisan Commands
```bash
# Data updates (via provider abstraction)
php artisan market:sync                   # Sync all market data from provider
php artisan market:sync --symbol=AAPL     # Sync specific symbol
php artisan market:earnings               # Update earnings calendar

# Cache
php artisan cache:warm-up                 # Pre-warm caches
php artisan cache:clear                   # Clear all caches

# Database
php artisan migrate:fresh --seed          # Reset with seed data

# Provider testing
php artisan provider:test finnhub         # Test Finnhub connection
php artisan provider:test moomoo          # Test Moomoo connection
```

### API Endpoints (Internal)
```
GET  /api/earnings?weeks=2                # Earnings list
GET  /api/earnings?watchlist_only=true    # Filtered by watchlist
GET  /api/watchlist                       # User watchlist
POST /api/watchlist                       # Add to watchlist
DELETE /api/watchlist/{symbol}            # Remove from watchlist
GET  /api/company/{symbol}                # Company details
POST /api/trade                           # Place order
GET  /api/orders                          # Order history
```

### File Locations
```
Config: .env
Database: database/database.sqlite
Logs: storage/logs/laravel.log
Cache: storage/framework/cache/
Sessions: storage/framework/sessions/
```

---

**Document Status**: Unified and authoritative  
**Key Architecture**: Provider abstraction layer for market data (Finnhub/Polygon) and trading (Moomoo/Alpaca)  
**Data Source**: Finnhub paid tier (no Yahoo scraping required)  
**Supersedes**: prompt.md, PROJECT_STATUS.md, REQUIREMENTS_ADJUSTMENT_SUMMARY.md  
**Next Review**: After Phase 1 completion
