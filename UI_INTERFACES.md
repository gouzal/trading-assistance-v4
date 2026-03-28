# TradingAssistant — UI Interface Contracts

> For UI designers. Each section describes one interface: what the frontend sends, what it gets back, and which UI component consumes it.

---

## 1. Dashboard — Load Company List

**When:** Page load / date range change

```
GET /dashboard?range=2
```

| Param | Type | Values | Default |
|-------|------|--------|---------|
| `range` | integer | 1, 2, 3, 4 (weeks) | 2 |
| `favorites_only` | boolean | true / false | false |

**Response — array of company cards:**

```json
[
  {
    "symbol": "AAPL",
    "name": "Apple Inc.",
    "sector": "Technology",
    "logo": "https://...",
    "is_favorite": true,

    "earnings": {
      "date": "2025-12-12",
      "time": "BMO",            // "BMO" = before market, "AMC" = after, "DMH" = during
      "estimated_eps": 2.10,
      "actual_eps": null,       // null until announced
      "estimated_revenue": 94500000000,
      "actual_revenue": null
    },

    "price": {
      "current": 185.42,
      "change": 1.23,
      "change_percent": 0.67,
      "direction": "up"         // "up" | "down" | "flat"
    },

    "financials": {
      "fair_value": 200.00,
      "estimated_revenue": 94500000000,
      "revenue_turnover_pct": 8.3,
      "pe_ratio": 28.4,
      "week_52_high": 199.62,
      "week_52_low": 164.08
    },

    "sentiment": {
      "score": 0.42,             // -1.0 to 1.0
      "label": "Good",           // "Very Good" | "Good" | "Neutral" | "Bad" | "Very Bad"
      "analyst_rating": "Buy",   // "Strong Buy" | "Buy" | "Hold" | "Sell" | "Strong Sell"
      "revised": true,
      "revision_direction": "up" // "up" | "down" | null
    }
  }
]
```

**UI Components that use this:**
- Company card (all fields)
- Favorites toggle filter (`is_favorite`)
- Date range selector (drives the `range` param)

---

## 2. Symbol Search

**When:** User types in the search bar (debounced 300ms, min 1 char)

```
GET /api/symbols/search?q=APP
```

**Response — array of up to 8 results:**

```json
[
  {
    "symbol": "AAPL",
    "name": "Apple Inc.",
    "sector": "Technology",
    "already_in_list": false   // true = show "✓ Already watching" state (non-clickable)
  },
  {
    "symbol": "APPN",
    "name": "Appian Corporation",
    "sector": "Technology",
    "already_in_list": true
  }
]
```

**UI Component:** Search dropdown in dashboard header / watchlist page

---

## 3. Add Company

**When:** User selects a result from search and confirms

```
POST /companies
Content-Type: application/json
```

**Request body:**

```json
{
  "symbol": "NVDA",
  "is_favorite": true    // whether user checked "Mark as favorite"
}
```

**Response — the created company:**

```json
{
  "symbol": "NVDA",
  "name": "Nvidia Corporation",
  "sector": "Technology",
  "logo": "https://...",
  "is_favorite": true,
  "added_by": "user"
}
```

**Errors:**

```json
{ "error": "already_exists", "message": "NVDA is already in your list." }
{ "error": "not_found",      "message": "Symbol NVDA not found." }
```

---

## 4. Toggle Favorite

**When:** User clicks the ★/☆ star on any company card (no page reload)

```
PATCH /companies/{symbol}/favorite
```

No request body needed.

**Response:**

```json
{
  "symbol": "AAPL",
  "is_favorite": true    // the NEW state after toggle
}
```

**UI behavior:** On success, flip the star icon and add/remove the gold border on the card without reload.

---

## 5. Remove Company

**When:** User deletes a company from the manage list page

```
DELETE /companies/{symbol}
```

**Response:**

```json
{ "success": true }
```

**Note:** Only companies with `added_by = "user"` can be deleted. System-added companies are removed automatically when they no longer have upcoming earnings.

---

## 6. Live Price Refresh

**When:** Trading modal opens (needs fresh price before order)

```
GET /api/quotes/{symbol}
```

**Response:**

```json
{
  "symbol": "AVGO",
  "current_price": 187.30,
  "change": 2.05,
  "change_percent": 1.11,
  "direction": "up",
  "volume": 1823400,
  "last_updated": "2025-12-10T14:23:00Z",
  "stale": false           // true if API was down and cached data is >5 min old
}
```

**UI Component:** Trading modal price display + "Last updated X minutes ago" line

---

## 7. Account Info

**When:** Trading modal opens (needed for quantity preset calculations)

```
GET /api/account
```

**Response:**

```json
{
  "cash": 25000.00,
  "buying_power": 25000.00,
  "portfolio_value": 37450.00,
  "equity": 62450.00,
  "currency": "USD",
  "is_paper_account": true,

  "position": {             // position for the symbol being traded (null if none held)
    "symbol": "AVGO",
    "quantity": 0,
    "average_cost": null,
    "current_price": 187.30,
    "market_value": 0,
    "unrealized_pl": null,
    "unrealized_pl_percent": null
  },

  "all_positions": [        // full portfolio — used for portfolio summary view
    {
      "symbol": "AAPL",
      "quantity": 10,
      "average_cost": 180.00,
      "current_price": 185.42,
      "market_value": 1854.20,
      "unrealized_pl": 54.20,
      "unrealized_pl_percent": 3.01
    },
    {
      "symbol": "MSFT",
      "quantity": 5,
      "average_cost": 400.00,
      "current_price": 412.10,
      "market_value": 2060.50,
      "unrealized_pl": 60.50,
      "unrealized_pl_percent": 3.03
    }
  ]
}
```

**UI Component:** Trading modal — drives the quantity preset buttons:
- `[1/2]` → `floor(buying_power * 0.5 / current_price)` shares (buy) or `floor(position.quantity / 2)` (sell)
- `[1/4]` → 25% of above
- `[1/25]` → 4% of above
- `[All]` → full buying power or entire position

---

## 8. Place Order

**When:** User confirms order in trading modal

```
POST /orders
Content-Type: application/json
```

**Request body:**

```json
{
  "symbol": "AVGO",
  "side": "buy",          // "buy" | "sell"
  "order_type": "market", // "market" | "limit"
  "quantity": 33,
  "limit_price": null     // required when order_type = "limit"
}
```

**Response — success:**

```json
{
  "success": true,
  "order": {
    "id": 42,
    "alpaca_order_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "symbol": "AVGO",
    "side": "buy",
    "order_type": "market",
    "quantity": 33,
    "limit_price": null,
    "status": "submitted",  // "submitted" | "filled" | "pending"
    "submitted_at": "2025-12-10T14:25:00Z"
  }
}
```

**Response — error:**

```json
{
  "success": false,
  "error": "insufficient_funds",
  "message": "Not enough buying power. Required: $6,180.90, Available: $5,200.00"
}
```

**Possible error codes:** `insufficient_funds`, `market_closed`, `invalid_quantity`, `trading_disabled`, `paper_mode`

**UI Component:** Trading modal confirmation step + toast notification on result

---

## 9. Order History

**When:** User opens the Orders page

```
GET /orders?limit=50
```

**Response:**

```json
[
  {
    "id": 42,
    "symbol": "AVGO",
    "company_name": "Broadcom Inc.",
    "side": "buy",
    "order_type": "market",
    "quantity": 33,
    "limit_price": null,
    "executed_price": 187.45,
    "status": "filled",      // "pending" | "submitted" | "filled" | "partial" | "cancelled" | "failed"
    "submitted_at": "2025-12-10T14:25:00Z",
    "executed_at": "2025-12-10T14:25:02Z"
  }
]
```

---

## 10. Company List (Watchlist / Manage Page)

**When:** User opens the `/companies` management page

```
GET /companies
```

No params.

**Response — full list of all companies in the DB:**

```json
[
  {
    "symbol": "AAPL",
    "name": "Apple Inc.",
    "sector": "Technology",
    "industry": "Consumer Electronics",
    "country": "US",
    "logo": "https://...",
    "is_favorite": true,
    "added_by": "user",      // "user" | "system"
    "notes": null,

    "price": {
      "current": 185.42,
      "change": 1.23,
      "change_percent": 0.67,
      "direction": "up"
    },

    "next_earnings": {
      "date": "2025-12-12",
      "time": "BMO"
    }                        // null if no upcoming earnings
  }
]
```

**UI Component:** Companies management page — shows all tracked companies with:
- Star toggle (favorite)
- Delete button (only shown when `added_by = "user"`)
- Next earnings date
- Current price

---

## 11. Get User Settings

**When:** Settings page loads (populate form with current values)

```
GET /settings
```

**Response:**

```json
{
  "earnings_date_range": 2,
  "theme": "dark",
  "default_order_type": "market"
}
```

---

## 13. Update User Settings

**When:** User changes date range or theme in settings

```
PATCH /settings
Content-Type: application/json
```

**Request body (all fields optional):**

```json
{
  "earnings_date_range": 3,   // 1 | 2 | 3 | 4 (weeks)
  "theme": "dark",            // "dark" | "light"
  "default_order_type": "market"  // "market" | "limit"
}
```

**Response:**

```json
{ "success": true }
```

---

## Global States the UI Must Handle

| State | When | UI Treatment |
|-------|------|-------------|
| `stale: true` on price | API down, showing cached data | Show yellow "⚠ Cached data" badge on card |
| `is_paper_account: true` | Always during dev/staging | Show "Paper Trading" banner in modal |
| `trading_disabled` error | `ENABLE_TRADING=false` in env | Disable Buy/Sell buttons, show "Trading unavailable" tooltip |
| `market_closed` error | Outside market hours | Show in modal, still allow limit orders |
| Empty earnings list | No companies with earnings in range | Show empty state illustration |

---

## Sentiment Score → Visual Bar

The `sentiment.score` (-1.0 to 1.0) drives the progress bar width:

```
score  =  1.0  →  bar width 100%,  color green
score  =  0.0  →  bar width 50%,   color gray
score  = -1.0  →  bar width 0%,    color red
```

Formula: `bar_width = ((score + 1) / 2) * 100`

| Label | Score Range | Bar Color |
|-------|-------------|-----------|
| Very Good | ≥ 0.6 | Green `#10b981` |
| Good | ≥ 0.3 | Light Green |
| Neutral | ≥ -0.3 | Gray |
| Bad | ≥ -0.6 | Orange |
| Very Bad | < -0.6 | Red `#ef4444` |

---

## Announcement Time Labels

| Raw Value | Display |
|-----------|---------|
| `BMO` | Before Market Open |
| `AMC` | After Market Close |
| `DMH` | During Market Hours |
| `null` | Time TBD |
