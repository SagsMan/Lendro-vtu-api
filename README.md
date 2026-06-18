# Lendro VTU API

**Live Site:** https://lendro.trackd.live  
**API Base URL:** https://lendro.trackd.live/api/v1  
**Contact:** sagirugarba24@gmail.com | 08065488451

---

A multi-provider Virtual Top-Up (VTU) platform built in PHP. Users can buy airtime, data bundles, electricity tokens, cable TV subscriptions, and exam PINs — all in one place. Prices are pulled from multiple VTU providers, normalised, marked up, and served through one clean JSON interface.

---

## Table of Contents

1. [How It Works](#how-it-works)
2. [Frontend Pages](#frontend-pages)
3. [Authentication](#authentication)
4. [API Endpoints](#api-endpoints)
5. [Order Flow (Step by Step)](#order-flow-step-by-step)
6. [Transaction Statuses](#transaction-statuses)
7. [Error Responses](#error-responses)
8. [Database Tables](#database-tables)
9. [Project Structure](#project-structure)
10. [Contact](#contact)

---

## How It Works

```
1. User opens index.html → registers or logs in
2. On success → redirected to app.html (the main dashboard)
3. Dashboard shows Partner Services grid (Airtel, MTN, GLO, 9mobile, Data, Cable TV, Electricity, More)
4. User picks a service, enters recipient phone number
5. Wallet is debited instantly → transaction queued in background
6. App polls GET /client/status every 5 seconds
7. Background worker sends request to VTU provider
8. If SUCCESS  → transaction marked done, user notified
9. If FAILED   → wallet refunded automatically
10. If PENDING  → reconciler checks again later
```

---

## Frontend Pages

| File           | Purpose                                                          |
|----------------|------------------------------------------------------------------|
| `index.html`   | Login / Register page (React, Tailwind CDN)                      |
| `app.html`     | Main dashboard after login (vanilla JS, CSS variables)           |

### Dashboard — Partner Services

The dashboard (`app.html`) opens on a **Home** tab showing a **Partner Services** grid:

| Icon        | Action                                    |
|-------------|-------------------------------------------|
| Airtel      | → Airtime tab, Airtel network pre-selected |
| MTN         | → Airtime tab, MTN network pre-selected   |
| GLO         | → Airtime tab, GLO network pre-selected   |
| 9mobile     | → Airtime tab, 9mobile pre-selected       |
| Data        | → Data tab with duration filters          |
| Cable TV    | → Cable TV tab                            |
| Electricity | → Electricity tab                         |
| More        | → Airtime tab (full service browser)      |

### Data Duration Filters

When viewing the Data tab, bundles can be filtered by:
- **All** — show everything
- **🔥 Hot Deal** — cheapest 6 plans
- **Daily** — 1-2 day validity
- **Weekly** — 5-10 day / 1-week validity
- **Monthly** — 25+ day / monthly validity

---

## Authentication

Sessions are **cookie-based**. After a successful login, the server sets a PHP session cookie.

```js
fetch(url, { credentials: 'include' })
```

Unauthorised response:
```json
{ "status": "failed", "message": "Unauthorized. Please log in." }
```

---

## API Endpoints

All requests/responses use **JSON**. Set: `Content-Type: application/json`

### Auth

| Method | Endpoint                  | Description         |
|--------|---------------------------|---------------------|
| POST   | `/auth/register.php`      | Create account      |
| POST   | `/auth/login.php`         | Log in              |
| POST   | `/auth/logout.php`        | End session         |
| POST   | `/auth/auth.php`          | Check session state |
| POST   | `/auth/forgot-pwd.php`    | Request PIN reset   |

### Client (🔐 Login required)

| Method | Endpoint                      | Description                        |
|--------|-------------------------------|------------------------------------|
| GET    | `/client/services.php`        | All services (airtime/data/bills)  |
| POST   | `/client/order.php`           | Place a purchase order             |
| GET    | `/client/status.php?ref=…`    | Poll order status                  |
| GET    | `/client/transactions.php`    | Transaction history                |
| GET    | `/client/wallet.php`          | Wallet balance                     |
| POST   | `/client/plan.php`            | Get plan details                   |
| POST   | `/client/show.php`            | Get services by type/network       |

### Accounts (🔐 Login required)

| Method | Endpoint                          | Description                  |
|--------|-----------------------------------|------------------------------|
| POST   | `/accounts/home.php`              | Dashboard data (wallet, txns)|
| POST   | `/accounts/deposit.php`           | Initiate wallet deposit      |
| POST   | `/accounts/initiate_payment.php`  | Payment initiation           |
| POST   | `/accounts/contest.php`           | Contest entry                |
| POST   | `/accounts/leaderboard.php`       | Leaderboard data             |
| POST   | `/accounts/join_with_wallet.php`  | Join with wallet             |

### Webhooks

| Method | Endpoint                          | Description              |
|--------|-----------------------------------|--------------------------|
| POST   | `/webhooks/provider.php`          | Provider callback (VTU)  |

### Cron Jobs

| Script                              | Schedule       | Purpose                          |
|-------------------------------------|----------------|----------------------------------|
| `cronjob/populate-services.php`     | Every 4 days   | Sync provider service catalogue  |
| `cronjob/sync_provider_services.php`| On demand      | Force-sync specific provider     |
| `cronjob/reconcile_transactions.php`| Every 5 min    | Follow up on stuck transactions  |

---

## Order Flow (Step by Step)

```
Step 1 — Login
  POST /auth/login.php { phone, pin }
  → session cookie set; redirected to app.html

Step 2 — Load Services
  GET /client/services.php
  → services grouped by type & network displayed in cards

Step 3 — Place Order
  User picks a service, enters phone number
  POST /client/order.php { service_id, phone, idempotency_key }
  → wallet debited, receive a reference

Step 4 — Poll Status
  Every 5 seconds: GET /client/status.php?ref=LDR-xxx
  → stop when tx_status is "success", "reversed", or "failed"
  → stop after 2 minutes

Step 5 — Show Result
  "success"  → success banner, refresh wallet
  "reversed" → refund message, refresh wallet
  "failed"   → error message
```

---

## Transaction Statuses

| Status                    | Meaning                                             | Wallet   |
|---------------------------|-----------------------------------------------------|----------|
| `pending`                 | Queued, not yet picked by worker                    | Debited  |
| `processing`              | Sent to provider, awaiting response                 | Debited  |
| `success`                 | Provider confirmed delivery ✅                      | Debited  |
| `awaiting_reconciliation` | Provider said "pending" — reconciler will follow up | Debited  |
| `reversed`                | Failed — wallet refunded automatically ↩️           | Refunded |
| `failed`                  | Hard failure — wallet refunded ↩️                  | Refunded |

---

## Error Responses

```json
{ "status": "failed", "message": "Human-readable explanation." }
```

---

## Database Tables

| Table                | Purpose                                              |
|----------------------|------------------------------------------------------|
| `users`              | User accounts                                        |
| `wallets`            | Naira balance per user                               |
| `wallet_logs`        | Debit/credit audit trail                             |
| `providers`          | VTU provider config                                  |
| `services`           | Normalised service catalogue                         |
| `provider_services`  | Maps our services → provider product IDs             |
| `transactions`       | Every purchase attempt with full status history      |
| `transaction_queue`  | Async job queue for background worker                |
| `provider_callbacks` | Raw webhook payloads                                 |
| `notifications`      | In-app notifications                                 |
| `apicache`           | Cached provider product lists                        |
| `commissions`        | Profit tracking per transaction                      |

Full schema: `dbmlendro.sql`

---

## Project Structure

```
/
├── index.html                          ← Login / Register page
├── app.html                            ← Main dashboard (Partner Services + tabs)
├── assets/
│   └── components/
│       └── ehelper.jsx                 ← Shared JS helpers (storage, API base URL)
├── api/
│   └── v1/
│       ├── configs.php                 ← DB, URLs, API keys, markup config
│       ├── db.php                      ← PDO database connection
│       ├── TransactionService.php      ← Wallet debit + queue push
│       ├── TransactionService_Sync.php ← Synchronous transaction flow
│       ├── ServiceManager.php          ← Service catalogue queries
│       ├── IdempotencyService.php      ← Prevents duplicate purchases
│       ├── ProviderFactory.php         ← Builds provider instances
│       ├── ProviderInterface.php       ← Contract every provider must follow
│       ├── Normalizer.php              ← Maps webhook payloads to internal format
│       ├── ProviderResponseNormalizer.php
│       ├── AI-question.php             ← AI-based question endpoint
│       ├── auth/
│       │   ├── auth.php                ← Session check
│       │   ├── index.php               ← Auth index
│       │   ├── login.php               ← POST /auth/login
│       │   ├── logout.php              ← POST /auth/logout
│       │   ├── register.php            ← POST /auth/register
│       │   └── forgot-pwd.php          ← PIN reset request
│       ├── accounts/
│       │   ├── home.php                ← Dashboard data (wallet, txns, leaderboard)
│       │   ├── deposit.php             ← Wallet deposit
│       │   ├── initiate_payment.php    ← Payment initiation
│       │   ├── contest.php             ← Contest entry
│       │   ├── leaderboard.php         ← Leaderboard
│       │   ├── join_with_wallet.php    ← Join with wallet
│       │   └── index.php               ← Accounts index
│       ├── client/
│       │   ├── services.php            ← GET /client/services
│       │   ├── order.php               ← POST /client/order
│       │   ├── status.php              ← GET /client/status
│       │   ├── transactions.php        ← GET /client/transactions
│       │   ├── wallet.php              ← GET /client/wallet
│       │   ├── plan.php                ← Plan details
│       │   └── show.php                ← Services by type/network
│       ├── providers/
│       │   ├── BaseProvider.php
│       │   ├── ProviderA.php           ← CheapDataHub
│       │   ├── ProviderB.php           ← ConnectBridge
│       │   └── ProviderProductsA.php
│       ├── helpers/
│       │   ├── helpers.php
│       │   ├── fxn-general.php
│       │   └── QueueHelper.php
│       ├── cronjob/
│       │   ├── populate-services.php
│       │   ├── sync_provider_services.php
│       │   ├── reconcile_transactions.php
│       │   └── index.php
│       ├── webhooks/
│       │   └── provider.php
│       └── workers/
│           ├── process_transactions.php
│           └── lendro-worker.conf
└── dbmlendro.sql                       ← Full database schema + seed data
```

---

## Supported Providers

| Provider      | Slug            | Services                                        |
|---------------|-----------------|-------------------------------------------------|
| CheapDataHub  | `cheapdatahub`  | Airtime, Data, Electricity, Cable TV, Exam PINs |
| ConnectBridge | `connectbridge` | Airtime, Data (fallback)                        |

---

## Contact

**Email:** sagirugarba24@gmail.com  
**Phone / WhatsApp:** 08065488451
