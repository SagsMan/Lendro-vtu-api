# Lendro VTU API

A multi-provider Virtual Top-Up (VTU) API built in PHP. It pulls products from multiple VTU providers, normalises them into a single catalogue, marks up prices, and lets users buy airtime, data, electricity, cable TV subscriptions, and exam PINs — all through one clean interface.

---

## What it does (objectives solved)

| # | Objective | Status |
|---|-----------|--------|
| 1 | Pull products from multiple VTU providers and normalise to our service catalogue | ✅ Done |
| 2 | Apply markup to provider prices and save normalised services to database | ✅ Done |
| 3 | Display services on a UI for users to pick (airtime, data, electricity, cable, exam PIN) | ✅ Done |
| 4 | User picks a service → API debits wallet, queues transaction, loops through providers | ✅ Done |
| 5 | Background worker sends purchase request to provider and logs the result | ✅ Done |
| 6 | Success → mark complete; Pending → reconcile; Failed → refund wallet | ✅ Done |
| 7 | Webhook handler processes real-time provider callbacks | ✅ Done |
| 8 | Idempotency protection — no duplicate purchases on network retries | ✅ Done |

---

## How it works (architecture)

```
User Request (POST /client/order.php)
    ↓
Validate + Debit Wallet
    ↓
Create PENDING transaction
    ↓
Push to transaction_queue
    ↓
Return "processing" to user immediately ← fast response, no waiting

BACKGROUND WORKER (workers/process_transactions.php)
    ↓ picks up queue item
    ↓ loops through providers in priority order
    ↓ calls provider API

    IF SUCCESS  → mark transaction "success"    → notify user
    IF PENDING  → mark "awaiting_reconciliation" → reconciler checks later
    IF FAILED   → try next provider
    IF ALL FAIL → refund wallet, mark "reversed"

RECONCILIATION WORKER (cronjob/reconcile_transactions.php)
    ↓ checks pending transactions with provider
    IF SUCCESS  → finalize + notify
    IF FAILED   → refund + notify
    IF MAX ATTEMPTS → give up + refund

WEBHOOK (webhooks/provider.php)
    ↓ provider POSTs real-time result
    ↓ normalise payload, update transaction and queue
```

---

## Project structure

```
api/
└── v1/
    ├── configs.php                  ← environment config, markup rate, API keys
    ├── db.php                       ← database connection bootstrap
    │
    ├── ProviderInterface.php        ← contract every provider must implement
    ├── ProviderFactory.php          ← builds provider instances from DB config
    ├── ProviderResponseNormalizer.php ← maps provider status → success/pending/failed
    ├── Normalizer.php               ← maps webhook payloads from any provider
    ├── ServiceManager.php           ← service catalogue queries
    ├── TransactionService.php       ← wallet debit + queue push
    ├── IdempotencyService.php       ← prevents duplicate transactions
    │
    ├── providers/
    │   ├── BaseProvider.php         ← shared HTTP helper
    │   ├── ProviderA.php            ← CheapDataHub integration
    │   ├── ProviderB.php            ← ConnectBridge integration
    │   └── ProviderProductsA.php   ← scrapes CheapDataHub plan table
    │
    ├── helpers/
    │   ├── helpers.php              ← general helpers (auth, phone, wallet, refs)
    │   ├── QueueHelper.php          ← queue status update helpers
    │   └── fxn-general.php         ← API response cache helpers
    │
    ├── auth/
    │   ├── login.php               ← POST — authenticate and start session
    │   ├── register.php            ← POST — create new account
    │   └── logout.php              ← POST — destroy session
    │
    ├── client/
    │   ├── services.php            ← GET  — list all services (grouped)
    │   ├── order.php               ← POST — place a purchase order
    │   ├── status.php              ← GET  — poll transaction status by ref
    │   ├── wallet.php              ← GET  — wallet balance + recent transactions
    │   └── transactions.php        ← GET  — full transaction history
    │
    ├── cronjob/
    │   ├── populate-services.php   ← syncs provider catalogues → our services table
    │   └── reconcile_transactions.php ← follows up on pending provider responses
    │
    ├── workers/
    │   ├── process_transactions.php ← long-running worker that calls provider APIs
    │   └── lendro-worker.conf       ← Supervisor config to keep workers alive
    │
    └── webhooks/
        └── provider.php            ← receives real-time callbacks from providers

public/
└── index.php                       ← single-page frontend (login, buy, history)

dbmlendro.sql                       ← complete database schema
```

---

## Database tables

| Table | Purpose |
|-------|---------|
| `users` | User accounts |
| `wallets` | Each user's balance |
| `wallet_logs` | Full debit/credit audit trail |
| `providers` | VTU provider config (name, base_url, api_key) |
| `services` | Our normalised service catalogue |
| `provider_services` | Maps our services → provider's internal SKU + cost price |
| `transactions` | Every purchase attempt with full status history |
| `transaction_queue` | Async job queue consumed by the background worker |
| `provider_callbacks` | Raw webhook payloads from providers (audit log) |
| `notifications` | In-app user notifications |
| `apicache` | Cached provider product lists |
| `commissions` | Profit tracking per transaction |

---

## Getting started

### 1. Import the database

```bash
mysql -u root -p -e "CREATE DATABASE dbmlendro CHARACTER SET utf8mb4;"
mysql -u root -p dbmlendro < dbmlendro.sql
```

### 2. Configure your environment

Edit `api/v1/configs.php` and fill in your real values:

```php
// Database
$host     = 'localhost';
$dbname   = 'dbmlendro';
$username = 'root';
$password = 'yourpassword';

// Markup applied on top of provider cost prices
define('MARKUP', 0.15);  // 15%
```

Or set environment variables (recommended for production):

```
DB_HOST=localhost
DB_NAME=dbmlendro
DB_USER=root
DB_PASS=yourpassword
```

### 3. Set provider API keys in the database

```sql
UPDATE providers SET api_key = 'your-cheapdatahub-key' WHERE slug = 'cheapdatahub';
UPDATE providers SET api_key = 'your-connectbridge-key' WHERE slug = 'connectbridge';
```

### 4. Sync the service catalogue

Run this once to populate services from all providers:

```bash
php api/v1/cronjob/populate-services.php
```

Then set a cron job to keep it fresh:

```
# Sync every 6 hours
0 */6 * * * php /var/www/html/api/v1/cronjob/populate-services.php >> /var/log/lendro-sync.log 2>&1
```

### 5. Start the background worker

```bash
# Run directly (for testing)
php api/v1/workers/process_transactions.php

# OR use Supervisor for production (keeps it running forever)
sudo cp api/v1/workers/lendro-worker.conf /etc/supervisor/conf.d/lendro.conf
sudo supervisorctl reread && sudo supervisorctl update
```

### 6. Set up webhook URLs with your providers

Tell each provider to POST callbacks to:

```
https://yourdomain.com/api/v1/webhooks/provider.php?provider=cheapdatahub
https://yourdomain.com/api/v1/webhooks/provider.php?provider=connectbridge
```

### 7. Open the frontend

Navigate to `https://yourdomain.com/public/index.php` in a browser.

---

## API endpoints

All endpoints return JSON. Protected endpoints require a valid session (login first).

### Auth

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/register.php` | Create account (`name`, `email`, `phone`, `password`) |
| POST | `/api/v1/auth/login.php` | Login (`email`, `password`) |
| POST | `/api/v1/auth/logout.php` | Logout (destroys session) |

### Services

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/client/services.php` | List all services grouped by type/network |
| GET | `/api/v1/client/services.php?type=data&network=mtn` | Filter by type and/or network |

### Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/client/order.php` | Place a purchase (`service_id`, `phone`, `idempotency_key`) |
| GET | `/api/v1/client/status.php?ref=LDR-xxx` | Poll transaction status |

### Wallet & History

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/client/wallet.php` | Balance + last 20 transactions |
| GET | `/api/v1/client/transactions.php` | Full paginated history |

### Webhooks (called by providers, not users)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/webhooks/provider.php?provider=cheapdatahub` | Receive provider callback |

---

## Transaction status lifecycle

```
pending → processing → success
                     → reversed   (failed + refunded)
                     → timeout    (max retries exceeded)
```

| Status | Meaning |
|--------|---------|
| `pending` | Queued, worker hasn't picked it up yet |
| `processing` | Worker called provider; provider is processing |
| `success` | Delivered successfully |
| `failed` | All providers rejected the request |
| `reversed` | Failed after the wallet was debited — wallet refunded |
| `timeout` | Max reconciliation attempts reached — wallet refunded |

---

## Adding a new provider

1. Create a new class in `api/v1/providers/ProviderC.php` that extends `BaseProvider` and implements `ProviderInterface`.
2. Add a `case 'newprovider':` in `ProviderFactory::make()`.
3. Insert the provider's config into the `providers` table.
4. Run `populate-services.php` to sync their catalogue.

---

## Security notes

- All purchase endpoints require a valid PHP session (login).
- Idempotency keys prevent double-charging on network retries.
- Wallet updates use `FOR UPDATE` row locks to prevent race conditions.
- Webhook signatures can be verified per-provider (see `webhooks/provider.php`).
- Never commit real API keys — use environment variables in production.
- All DB queries use PDO prepared statements — no raw string interpolation.

---

## Providers supported

| Provider | Slug | Services |
|----------|------|----------|
| CheapDataHub | `cheapdatahub` | Airtime, Data, Electricity, Cable, Exam PINs |
| ConnectBridge | `connectbridge` | Airtime, Data (fallback) |

---

## License

MIT — free to use and modify.
