# Lendro VTU API

**Live Site:** https://lendro.trackd.live  
**API Base URL:** https://lendro.trackd.live/api/v1  
**Contact:** sagirugarba24@gmail.com | 08065488451

---

A multi-provider Virtual Top-Up (VTU) API built in PHP. It lets your users buy airtime, data bundles, electricity tokens, cable TV subscriptions, and exam PINs — all in one place. Prices are pulled from multiple VTU providers, normalised, marked up, and served through one clean JSON interface.

---

## Table of Contents

1. [How It Works](#how-it-works)
2. [Authentication](#authentication)
3. [API Endpoints](#api-endpoints)
   - [Register](#1-register)
   - [Login](#2-login)
   - [Logout](#3-logout)
   - [Get Services](#4-get-services)
   - [Place an Order](#5-place-an-order)
   - [Check Order Status](#6-check-order-status)
   - [Transaction History](#7-transaction-history)
   - [Wallet Balance](#8-wallet-balance)
   - [Provider Webhook](#9-provider-webhook)
4. [Order Flow (Step by Step)](#order-flow-step-by-step)
5. [Transaction Statuses](#transaction-statuses)
6. [Error Responses](#error-responses)
7. [Database Tables](#database-tables)
8. [Project Structure](#project-structure)
9. [Contact](#contact)

---

## How It Works

```
1. User registers/logs in → gets a session cookie
2. App fetches available services → shows them to the user
3. User picks a service → App calls POST /client/order
4. Wallet is debited instantly → transaction queued in background
5. App polls GET /client/status every 5 seconds
6. Background worker sends request to VTU provider
7. If SUCCESS  → transaction marked done, user notified
8. If FAILED   → wallet refunded automatically
9. If PENDING  → reconciler checks again later
```

> **Important:** All endpoints under `/client/` require the user to be logged in. The session is cookie-based — just keep credentials: "include" in your fetch calls.

---

## Authentication

Sessions are **cookie-based**. After a successful login, the server sets a PHP session cookie. Send it automatically on every subsequent request by using:

```js
fetch(url, { credentials: 'include' })
```

If a protected endpoint is called without a valid session, you'll get:

```json
{
  "status": "failed",
  "message": "Unauthorized. Please log in."
}
```
HTTP status: `401`

---

## API Endpoints

All requests and responses use **JSON**. Always set the header:
```
Content-Type: application/json
```

---

### 1. Register

**Create a new user account.**

```
POST https://lendro.trackd.live/api/v1/auth/register
```

**Request Body:**
```json
{
  "name":     "Sagiru Garba",
  "email":    "sagirugarba24@gmail.com",
  "phone":    "08065488451",
  "password": "YourPassword123"
}
```

| Field      | Type   | Required | Notes                        |
|------------|--------|----------|------------------------------|
| `name`     | string | ✅ Yes   | User's full name             |
| `email`    | string | ✅ Yes   | Must be a valid email        |
| `phone`    | string | ✅ Yes   | Nigerian phone number        |
| `password` | string | ✅ Yes   | Minimum 8 characters         |

**Success Response** — HTTP 201:
```json
{
  "status":  "success",
  "message": "Account created successfully. Please log in.",
  "user_id": 42
}
```

**Failure Responses:**

Email already taken — HTTP 409:
```json
{
  "status":  "failed",
  "message": "An account with this email already exists."
}
```

Validation errors — HTTP 422:
```json
{
  "status": "failed",
  "errors": [
    "A valid email address is required.",
    "Password must be at least 8 characters."
  ]
}
```

---

### 2. Login

**Authenticate a user and start a session.**

```
POST https://lendro.trackd.live/api/v1/auth/login
```

**Request Body:**
```json
{
  "email":    "sagirugarba24@gmail.com",
  "password": "YourPassword123"
}
```

| Field      | Type   | Required |
|------------|--------|----------|
| `email`    | string | ✅ Yes   |
| `password` | string | ✅ Yes   |

**Success Response** — HTTP 200:
```json
{
  "status":  "success",
  "message": "Welcome back, Sagiru Garba!",
  "user": {
    "id":             42,
    "name":           "Sagiru Garba",
    "email":          "sagirugarba24@gmail.com",
    "phone":          "08065488451",
    "wallet_balance": 5000.00
  }
}
```

**Failure Response** — HTTP 401:
```json
{
  "status":  "failed",
  "message": "Incorrect email or password."
}
```

> After a successful login, the server sets a session cookie automatically. All subsequent calls to `/client/*` will be authenticated as long as you pass `credentials: "include"`.

---

### 3. Logout

**End the current session.**

```
POST https://lendro.trackd.live/api/v1/auth/logout
```

No request body needed.

**Success Response** — HTTP 200:
```json
{
  "status":  "success",
  "message": "You have been logged out."
}
```

---

### 4. Get Services

**Fetch all available services (airtime, data, bills, etc.).**

> 🔐 Requires login.

```
GET https://lendro.trackd.live/api/v1/client/services
```

**Optional Query Parameters:**

| Param      | Type   | Example          | Description                              |
|------------|--------|------------------|------------------------------------------|
| `type`     | string | `?type=data`     | Filter: `airtime`, `data`, `bill`        |
| `network`  | string | `?network=mtn`   | Filter by network: `mtn`, `glo`, `airtel`, `9mobile` |

**Examples:**
```
GET /api/v1/client/services               → all services
GET /api/v1/client/services?type=data     → data plans only
GET /api/v1/client/services?type=data&network=mtn  → MTN data plans only
```

**Success Response** — HTTP 200:
```json
{
  "status": "success",
  "data": {
    "airtime": {
      "mtn":    [
        { "id": 1, "key": "mtn-airtime", "name": "MTN Airtime", "price": null, "category": "airtime", "duration": null, "unit": null }
      ],
      "glo":    [ ... ],
      "airtel": [ ... ],
      "9mobile":[ ... ]
    },
    "data": {
      "mtn": [
        { "id": 5,  "key": "mtn-1gb-30d",  "name": "1GB — 30 Days",  "price": 350.00,  "category": "data", "duration": 30, "unit": "day" },
        { "id": 6,  "key": "mtn-2gb-30d",  "name": "2GB — 30 Days",  "price": 680.00,  "category": "data", "duration": 30, "unit": "day" }
      ]
    },
    "bill": {
      "electricity": [ ... ],
      "cable":       [ ... ]
    }
  }
}
```

Each service object:

| Field      | Type            | Description                                    |
|------------|-----------------|------------------------------------------------|
| `id`       | integer         | Use this as `service_id` when placing an order |
| `key`      | string          | Internal identifier                            |
| `name`     | string          | Human-readable name to display                 |
| `price`    | float or `null` | Fixed price in Naira; `null` = flexible amount |
| `category` | string          | Service category                               |
| `duration` | integer or null | Validity period number                         |
| `unit`     | string or null  | `"day"`, `"month"`, etc.                       |

---

### 5. Place an Order

**Buy a VTU service. The wallet is charged immediately, the delivery happens in the background.**

> 🔐 Requires login.

```
POST https://lendro.trackd.live/api/v1/client/order
```

**Request Body:**
```json
{
  "service_id":      5,
  "phone":           "08065488451",
  "idempotency_key": "550e8400-e29b-41d4-a716-446655440000"
}
```

| Field             | Type    | Required | Notes                                                                 |
|-------------------|---------|----------|-----------------------------------------------------------------------|
| `service_id`      | integer | ✅ Yes   | The `id` from GET /client/services                                    |
| `phone`           | string  | ✅ Yes   | Recipient's phone number                                              |
| `idempotency_key` | string  | ✅ Yes   | A unique UUID per order attempt — prevents duplicate charges on retry. Generate with `crypto.randomUUID()` |

> **Why idempotency_key?** If a network timeout causes your app to retry the request, the server will recognise the same key and return the original result instead of charging the wallet twice.

**Success Response** — HTTP 202:
```json
{
  "status":    "processing",
  "reference": "LDR-1718123456-A3F9",
  "message":   "Order received. Your service is being processed."
}
```

**Already processed (same idempotency_key sent again)** — HTTP 200:
```json
{
  "status":    "already_processed",
  "reference": "LDR-1718123456-A3F9",
  "message":   "This order was already submitted."
}
```

**Failure — insufficient balance** — HTTP 400:
```json
{
  "status":  "failed",
  "message": "Insufficient wallet balance."
}
```

**Failure — service not found** — HTTP 400:
```json
{
  "status":  "failed",
  "message": "Service not found or currently unavailable."
}
```

> After placing an order, **immediately start polling** the status endpoint every 5 seconds using the `reference` you received.

---

### 6. Check Order Status

**Poll this endpoint after placing an order to track its progress.**

> 🔐 Requires login.

```
GET https://lendro.trackd.live/api/v1/client/status?ref=LDR-1718123456-A3F9
```

| Query Param | Type   | Required | Description               |
|-------------|--------|----------|---------------------------|
| `ref`       | string | ✅ Yes   | The reference from /order |

**Success — transaction delivered** — HTTP 200:
```json
{
  "status":    "success",
  "tx_status": "success",
  "reference": "LDR-1718123456-A3F9",
  "service":   "MTN 1GB — 30 Days",
  "phone":     "08065488451",
  "amount":    350.00
}
```

**Still processing:**
```json
{
  "status":    "success",
  "tx_status": "processing",
  "reference": "LDR-1718123456-A3F9",
  "service":   "MTN 1GB — 30 Days",
  "phone":     "08065488451",
  "amount":    350.00
}
```

**Failed — wallet refunded:**
```json
{
  "status":    "success",
  "tx_status": "reversed",
  "reference": "LDR-1718123456-A3F9",
  "service":   "MTN 1GB — 30 Days",
  "phone":     "08065488451",
  "amount":    350.00
}
```

**Polling strategy:**
```
1. Place order → get reference
2. Poll every 5 seconds
3. Stop when tx_status is "success", "reversed", or "failed"
4. Stop after 2 minutes (24 attempts) — show a timeout message
```

---

### 7. Transaction History

**Get a paginated list of the user's past transactions.**

> 🔐 Requires login.

```
GET https://lendro.trackd.live/api/v1/client/transactions
```

**Optional Query Parameters:**

| Param    | Type    | Default | Description                                              |
|----------|---------|---------|----------------------------------------------------------|
| `page`   | integer | `1`     | Page number                                              |
| `limit`  | integer | `20`    | Items per page (max 50)                                  |
| `status` | string  | —       | Filter: `pending`, `processing`, `success`, `failed`, `reversed` |

**Examples:**
```
GET /api/v1/client/transactions                         → first 20 transactions
GET /api/v1/client/transactions?page=2&limit=10         → page 2
GET /api/v1/client/transactions?status=success          → successful only
```

**Success Response** — HTTP 200:
```json
{
  "status":      "success",
  "page":        1,
  "limit":       20,
  "total":       87,
  "total_pages": 5,
  "transactions": [
    {
      "reference":  "LDR-1718123456-A3F9",
      "service":    "MTN 1GB — 30 Days",
      "provider":   "CheapDataHub",
      "amount":     350.00,
      "phone":      "08065488451",
      "status":     "success",
      "created_at": "2025-06-15 14:32:00",
      "updated_at": "2025-06-15 14:32:18",
      "time_ago":   "2 days ago"
    },
    {
      "reference":  "LDR-1718099000-B7C1",
      "service":    "Airtel Airtime",
      "provider":   "ConnectBridge",
      "amount":     200.00,
      "phone":      "08012345678",
      "status":     "reversed",
      "created_at": "2025-06-14 09:10:00",
      "updated_at": "2025-06-14 09:10:45",
      "time_ago":   "3 days ago"
    }
  ]
}
```

---

### 8. Wallet Balance

**Get the logged-in user's current wallet balance and last 20 transactions.**

> 🔐 Requires login.

```
GET https://lendro.trackd.live/api/v1/client/wallet
```

No parameters required.

**Success Response** — HTTP 200:
```json
{
  "status":  "success",
  "balance": 4650.00,
  "transactions": [
    {
      "reference": "LDR-1718123456-A3F9",
      "amount":    350.00,
      "type":      "debit",
      "service":   "MTN 1GB — 30 Days",
      "status":    "success",
      "date":      "2025-06-15 14:32:00",
      "time_ago":  "2 days ago"
    }
  ]
}
```

**Failure — wallet missing** — HTTP 404:
```json
{
  "status":  "failed",
  "message": "Wallet not found."
}
```

---

### 9. Provider Webhook

**Endpoint that VTU providers call to deliver real-time status updates.**

> ⚠️ This is for providers only. Do not call this from your app.

```
POST https://lendro.trackd.live/api/v1/webhooks/provider?provider=cheapdatahub
```

| Query Param | Type   | Description                     |
|-------------|--------|---------------------------------|
| `provider`  | string | Provider slug (e.g. `cheapdatahub`, `connectbridge`) |

The server processes the callback, updates the transaction, and — if it failed — automatically refunds the user's wallet.

**Response when successful:**
```json
{ "status": "success" }
```

**Response when refunded:**
```json
{ "status": "refunded" }
```

**Response when still processing:**
```json
{ "status": "processing", "message": "Queued for reconciliation" }
```

---

## Order Flow (Step by Step)

Here is the full journey from opening the app to a completed purchase:

```
Step 1 — Register / Login
  POST /auth/register   (first time)
  POST /auth/login      (every session)
  → Save the session cookie

Step 2 — Load Services
  GET /client/services
  → Display service cards grouped by type & network

Step 3 — Place Order
  User picks a service, enters phone number
  Generate idempotency_key = crypto.randomUUID()
  POST /client/order  { service_id, phone, idempotency_key }
  → Wallet debited, get back a reference

Step 4 — Poll Status
  Every 5 seconds: GET /client/status?ref=LDR-xxx
  → Keep polling until tx_status is "success", "reversed", or "failed"
  → Stop after 2 minutes

Step 5 — Show Result
  "success"  → Show success screen, refresh wallet balance
  "reversed" → Show refund message, refresh wallet balance
  "failed"   → Show error message
```

---

## Transaction Statuses

| Status                    | Meaning                                                  | Wallet  |
|---------------------------|----------------------------------------------------------|---------|
| `pending`                 | Order received, not yet picked by worker                 | Debited |
| `processing`              | Worker sent request to provider, awaiting response       | Debited |
| `success`                 | Provider confirmed delivery ✅                           | Debited |
| `awaiting_reconciliation` | Provider said "pending" — reconciler will follow up      | Debited |
| `reversed`                | Delivery failed — wallet refunded automatically ↩️      | Refunded|
| `failed`                  | Hard failure — wallet refunded automatically ↩️         | Refunded|

---

## Error Responses

All errors follow this shape:

```json
{
  "status":  "failed",
  "message": "Human-readable explanation of what went wrong."
}
```

Or for validation with multiple issues:

```json
{
  "status": "failed",
  "errors": [
    "Name is required.",
    "Password must be at least 8 characters."
  ]
}
```

**Common HTTP status codes:**

| Code | Meaning                                    |
|------|--------------------------------------------|
| 200  | OK                                         |
| 201  | Created (registration)                     |
| 202  | Accepted (order placed, processing async)  |
| 400  | Bad request (failed order, invalid data)   |
| 401  | Unauthorized (not logged in)               |
| 404  | Not found                                  |
| 405  | Wrong HTTP method used                     |
| 409  | Conflict (e.g. email already registered)   |
| 422  | Validation error (missing required fields) |
| 500  | Server error                               |

---

## Database Tables

| Table                | Purpose                                               |
|----------------------|-------------------------------------------------------|
| `users`              | User accounts (name, email, phone, hashed password)   |
| `wallets`            | Each user's Naira balance                             |
| `wallet_logs`        | Full debit/credit audit trail                         |
| `providers`          | VTU provider config (name, base URL, API key)         |
| `services`           | Normalised service catalogue shown to users           |
| `provider_services`  | Maps our services → provider's internal product ID    |
| `transactions`       | Every purchase attempt with full status history        |
| `transaction_queue`  | Async job queue consumed by the background worker     |
| `provider_callbacks` | Raw webhook payloads saved for auditing               |
| `notifications`      | In-app user notifications                             |
| `apicache`           | Cached provider product lists (refreshed every 4 days)|
| `commissions`        | Profit tracking per transaction                       |

The database export (`dbmlendro.sql`) in this repo contains the full schema with all tables, indexes, and seed data. Import it to get started.

---

## Project Structure

```
/
├── index.php                          ← Root entry — loads the frontend
├── public/
│   └── index.php                      ← Full frontend SPA (HTML + JS)
├── api/
│   └── v1/
│       ├── configs.php                ← All config: DB, URLs, API keys, markup
│       ├── db.php                     ← Database connection (PDO)
│       ├── TransactionService.php     ← Core: wallet debit + queue push
│       ├── ServiceManager.php         ← Service catalogue queries
│       ├── IdempotencyService.php     ← Prevents duplicate purchases
│       ├── ProviderFactory.php        ← Builds provider instances
│       ├── ProviderInterface.php      ← Contract every provider must follow
│       ├── Normalizer.php             ← Maps webhook payloads to internal format
│       ├── ProviderResponseNormalizer.php ← Maps provider status → success/pending/failed
│       ├── auth/
│       │   ├── login.php              ← POST /auth/login
│       │   ├── logout.php             ← POST /auth/logout
│       │   └── register.php           ← POST /auth/register
│       ├── client/
│       │   ├── services.php           ← GET /client/services
│       │   ├── order.php              ← POST /client/order
│       │   ├── status.php             ← GET /client/status
│       │   ├── transactions.php       ← GET /client/transactions
│       │   └── wallet.php             ← GET /client/wallet
│       ├── providers/
│       │   ├── BaseProvider.php       ← Shared HTTP helper for all providers
│       │   ├── ProviderA.php          ← CheapDataHub integration
│       │   ├── ProviderB.php          ← ConnectBridge integration
│       │   └── ProviderProductsA.php  ← Scrapes CheapDataHub product list
│       ├── cronjob/
│       │   ├── populate-services.php  ← Syncs provider catalogues to DB
│       │   └── reconcile_transactions.php ← Follows up on pending transactions
│       ├── webhooks/
│       │   └── provider.php           ← POST /webhooks/provider (providers call this)
│       └── workers/
│           ├── process_transactions.php ← Background worker — sends to provider
│           └── lendro-worker.conf       ← Worker process config
└── dbmlendro.sql                      ← Full database schema + seed data
```

---

## Supported Providers

| Provider      | Slug            | Services Covered                                    |
|---------------|-----------------|-----------------------------------------------------|
| CheapDataHub  | `cheapdatahub`  | Airtime, Data, Electricity, Cable TV, Exam PINs     |
| ConnectBridge | `connectbridge` | Airtime, Data (used as fallback)                    |

To add a new provider:
1. Create `api/v1/providers/ProviderC.php` — extend `BaseProvider`, implement `ProviderInterface`
2. Add `case 'newprovider':` in `ProviderFactory::make()`
3. Insert a row in the `providers` table with the correct slug and API key
4. Run `populate-services.php` to sync their catalogue

---

## Contact

For integration support or API access:

**Email:** sagirugarba24@gmail.com  
**Phone / WhatsApp:** 08065488451

