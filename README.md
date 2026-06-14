# Lendro VTU API

A multi-provider Virtual Top-Up (VTU) API built in PHP. It pulls products from multiple VTU providers, normalises them into a single catalogue, marks up prices, and lets users buy airtime, data, electricity, cable TV subscriptions, and exam PINs — all through one clean interface.

---

## Objectives solved

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

RECONCILIATION CRON (cronjob/reconcile_transactions.php)
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
    ├── configs.php                    ← environment config, markup rate, API keys
    ├── db.php                         ← database connection bootstrap
    ├── ProviderInterface.php          ← contract every provider must implement
    ├── ProviderFactory.php            ← builds provider instances from DB config
    ├── ProviderResponseNormalizer.php ← maps provider status → success/pending/failed
    ├── Normalizer.php                 ← maps webhook payloads from any provider
    ├── ServiceManager.php             ← service catalogue queries
    ├── TransactionService.php         ← wallet debit + queue push
    ├── IdempotencyService.php         ← prevents duplicate transactions
    ├── providers/
    │   ├── BaseProvider.php           ← shared HTTP helper
    │   ├── ProviderA.php              ← CheapDataHub integration
    │   ├── ProviderB.php              ← ConnectBridge integration
    │   └── ProviderProductsA.php     ← scrapes CheapDataHub plan table
    ├── helpers/
    │   ├── helpers.php                ← auth, phone, wallet, ref helpers
    │   ├── QueueHelper.php            ← queue status update helpers
    │   └── fxn-general.php           ← API response cache helpers
    ├── auth/
    │   ├── login.php                  ← POST — authenticate and start session
    │   ├── register.php               ← POST — create new account
    │   └── logout.php                 ← POST — destroy session
    ├── client/
    │   ├── services.php               ← GET  — list all services (grouped)
    │   ├── order.php                  ← POST — place a purchase order
    │   ├── status.php                 ← GET  — poll transaction status by ref
    │   ├── wallet.php                 ← GET  — wallet balance + recent transactions
    │   └── transactions.php           ← GET  — full transaction history
    ├── cronjob/
    │   ├── populate-services.php      ← syncs provider catalogues → services table
    │   └── reconcile_transactions.php ← follows up on pending provider responses
    ├── workers/
    │   ├── process_transactions.php   ← worker that calls provider APIs
    │   └── lendro-worker.conf         ← Supervisor config (VPS only)
    └── webhooks/
        └── provider.php               ← receives real-time callbacks from providers

public/
└── index.php                          ← single-page frontend (login, buy, history)

dbmlendro.sql                          ← complete database schema
```

---

## Deploying on cPanel (Shared Hosting)

### Requirements

- PHP **8.0** or higher (PHP 8.1+ recommended)
- MySQL 5.7+ or MariaDB 10.3+
- cURL enabled (for provider API calls)
- cPanel File Manager or FTP access

---

### Step 1 — Download the repo

On your local machine, download or clone the repo:

```bash
git clone https://github.com/SagsMan/Lendro-vtu-api.git
```

Or download the ZIP from GitHub → **Code → Download ZIP**, then unzip it.

---

### Step 2 — Upload files to cPanel

1. Log in to **cPanel → File Manager**.
2. Navigate to `public_html` (or your subdomain/addon domain folder).
3. Create a folder called `lendro` (or leave it in the root).
4. Upload **all project files** into that folder, so the structure looks like:

```
public_html/
└── lendro/
    ├── api/
    ├── public/
    ├── dbmlendro.sql
    └── ...
```

> **Tip:** Use an FTP client like FileZilla for faster bulk uploads.

---

### Step 3 — Create the MySQL database

1. In cPanel, go to **MySQL Databases**.
2. Create a new database — e.g. `yourusername_lendro`.
3. Create a new user — e.g. `yourusername_lendrouser` — and set a strong password.
4. Add the user to the database and grant **All Privileges**.
5. Note down:
   - Database name: `yourusername_lendro`
   - Database user: `yourusername_lendrouser`
   - Password: `yourpassword`
   - Host: `localhost`

---

### Step 4 — Import the database schema

1. In cPanel, go to **phpMyAdmin**.
2. Click your database (`yourusername_lendro`) in the left panel.
3. Click the **Import** tab at the top.
4. Click **Choose File**, select `dbmlendro.sql` from your computer.
5. Click **Go**.

You should see a success message and the tables listed on the left.

---

### Step 5 — Configure `configs.php`

Open `api/v1/configs.php` in the File Manager editor (or via FTP) and update:

```php
// ── Database credentials ──────────────────────────────────────────────────────
$host     = 'localhost';
$dbname   = 'yourusername_lendro';
$username = 'yourusername_lendrouser';
$password = 'yourpassword';

// ── App base URL ──────────────────────────────────────────────────────────────
define('BASE_URL', 'https://yourdomain.com/lendro');

// ── Markup on provider cost prices ───────────────────────────────────────────
define('MARKUP', 0.15);  // 15% — adjust as you like
```

Also update the frontend API base URL in `public/index.php` (line ~388):

```js
const API = '/lendro/api/v1';  // adjust path to match where you uploaded the project
```

---

### Step 6 — Add provider API keys to the database

In **phpMyAdmin**, run these SQL queries (replace with your real keys):

```sql
UPDATE providers SET api_key = 'your-cheapdatahub-api-key' WHERE slug = 'cheapdatahub';
UPDATE providers SET api_key = 'your-connectbridge-api-key' WHERE slug = 'connectbridge';
```

To get keys:
- **CheapDataHub:** register at [cheapdatahub.ng](https://www.cheapdatahub.ng), go to your dashboard → API settings.
- **ConnectBridge:** register at [connectbridge.com.ng](https://connectbridge.com.ng), go to your dashboard → API settings.

---

### Step 7 — Set up cron jobs in cPanel

Go to cPanel → **Cron Jobs**.

Add the following two cron jobs (replace `/home/yourusername/public_html/lendro` with your actual path):

#### Service sync — runs every 6 hours
```
0 */6 * * *   php /home/yourusername/public_html/lendro/api/v1/cronjob/populate-services.php >> /home/yourusername/logs/lendro-sync.log 2>&1
```
This pulls the latest product catalogue from both providers and updates the services table.

#### Transaction worker — runs every minute
```
* * * * *   php /home/yourusername/public_html/lendro/api/v1/workers/process_transactions.php >> /home/yourusername/logs/lendro-worker.log 2>&1
```
On shared hosting you cannot run a permanent background process, so this cron triggers the worker every minute to process any queued transactions.

#### Reconciliation — runs every 15 minutes
```
*/15 * * * *   php /home/yourusername/public_html/lendro/api/v1/cronjob/reconcile_transactions.php >> /home/yourusername/logs/lendro-reconcile.log 2>&1
```
This follows up on any transactions still marked `pending` with the provider.

> **Log folder:** Create a `logs/` folder in your home directory first, or change the log paths to somewhere writable (e.g. `/home/yourusername/public_html/lendro/logs/`).

---

### Step 8 — Set provider webhook URLs

In each provider's dashboard, set the callback/webhook URL to:

```
https://yourdomain.com/lendro/api/v1/webhooks/provider.php?provider=cheapdatahub
https://yourdomain.com/lendro/api/v1/webhooks/provider.php?provider=connectbridge
```

This allows providers to notify you of transaction results in real time (instead of waiting for polling).

---

### Step 9 — Run the first service sync

Visit this URL in your browser once to populate the services table immediately (rather than waiting for the cron):

```
https://yourdomain.com/lendro/api/v1/cronjob/populate-services.php
```

You should see a JSON response listing synced services.

---

### Step 10 — Open the frontend

Visit:

```
https://yourdomain.com/lendro/public/index.php
```

Register an account, fund your wallet, and place a test order.

---

## Testing checklist

Work through these steps in order to confirm everything is wired up correctly.

### 1. Check database connection
```
https://yourdomain.com/lendro/api/v1/auth/login.php
```
Open in a browser — it should return JSON like `{"status":"failed","message":"Email and password are required."}` (not a PHP error or blank page). A PHP error here means the DB connection failed — recheck `configs.php`.

### 2. Register a test account
Use **Postman**, **Insomnia**, or the curl command below:

```bash
curl -X POST https://yourdomain.com/lendro/api/v1/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","phone":"08012345678","password":"test1234"}'
```

Expected response:
```json
{"status":"success","message":"Account created successfully.","user":{...}}
```

### 3. Log in

```bash
curl -c cookies.txt -X POST https://yourdomain.com/lendro/api/v1/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"test1234"}'
```

`-c cookies.txt` saves the session cookie for subsequent requests.

Expected response:
```json
{"status":"success","user":{"id":1,"name":"Test User","email":"test@example.com",...}}
```

### 4. Fund the test wallet (directly in phpMyAdmin)

Since Squad payment gateway is not wired up yet, fund the wallet directly via SQL:

```sql
UPDATE wallets SET balance = 5000.00 WHERE userid = 1;
```

### 5. Fetch available services

```bash
curl -b cookies.txt https://yourdomain.com/lendro/api/v1/client/services.php
```

Expected: grouped JSON with airtime, data, electricity, cable, education services.

If this returns an empty list, run the service sync (Step 9) first.

### 6. Place a test order

Get a `service_id` from the services response above, then:

```bash
curl -b cookies.txt -X POST https://yourdomain.com/lendro/api/v1/client/order.php \
  -H "Content-Type: application/json" \
  -H "X-Idempotency-Key: test-order-001" \
  -d '{"service_id":5,"phone":"08012345678"}'
```

Expected:
```json
{
  "status":"success",
  "message":"Order placed successfully. Processing in progress.",
  "reference":"LDR-XXXXXXXX",
  "transaction_status":"pending"
}
```

The wallet should be debited immediately. The transaction is now in the queue.

### 7. Check transaction status

```bash
curl -b cookies.txt "https://yourdomain.com/lendro/api/v1/client/status.php?ref=LDR-XXXXXXXX"
```

Keep polling every 10–15 seconds. Status will move from `pending` → `processing` → `success` (or `reversed` if the provider rejects it) once the cron worker runs.

### 8. Check wallet balance

```bash
curl -b cookies.txt https://yourdomain.com/lendro/api/v1/client/wallet.php
```

### 9. Full transaction history

```bash
curl -b cookies.txt https://yourdomain.com/lendro/api/v1/client/transactions.php
```

### 10. Test the frontend UI

Open `https://yourdomain.com/lendro/public/index.php` in a browser. Log in with the test account and go through: services list → pick a plan → enter phone → confirm order → poll status.

---

## API endpoints reference

All endpoints return JSON. Protected endpoints require a valid session cookie (log in first).

### Auth

| Method | Endpoint | Body / Params |
|--------|----------|---------------|
| POST | `/api/v1/auth/register.php` | `name`, `email`, `phone`, `password` |
| POST | `/api/v1/auth/login.php` | `email`, `password` |
| POST | `/api/v1/auth/logout.php` | — |

### Services

| Method | Endpoint | Params |
|--------|----------|--------|
| GET | `/api/v1/client/services.php` | — |
| GET | `/api/v1/client/services.php` | `?type=data&network=mtn` |

### Orders

| Method | Endpoint | Body / Params |
|--------|----------|---------------|
| POST | `/api/v1/client/order.php` | `service_id`, `phone`, `X-Idempotency-Key` header |
| GET | `/api/v1/client/status.php` | `?ref=LDR-xxx` |

### Wallet & History

| Method | Endpoint | Params |
|--------|----------|--------|
| GET | `/api/v1/client/wallet.php` | — |
| GET | `/api/v1/client/transactions.php` | — |

### Webhooks (provider → your server)

| Method | Endpoint | Params |
|--------|----------|--------|
| POST | `/api/v1/webhooks/provider.php` | `?provider=cheapdatahub` |

---

## Transaction status lifecycle

```
pending → processing → success
                     → reversed   (failed + wallet refunded)
                     → timeout    (max retries exceeded + wallet refunded)
```

| Status | Meaning |
|--------|---------|
| `pending` | Queued, worker hasn't picked it up yet |
| `processing` | Worker called provider; awaiting response |
| `awaiting_reconciliation` | Provider returned "pending" — reconciler will check |
| `success` | Delivered successfully |
| `failed` | All providers rejected |
| `reversed` | Failed after wallet debit — wallet refunded |
| `timeout` | Max reconciliation attempts reached — wallet refunded |

---

## Adding a new provider

1. Create `api/v1/providers/ProviderC.php` — extend `BaseProvider`, implement `ProviderInterface`.
2. Add `case 'newprovider':` in `ProviderFactory::make()`.
3. Insert into `providers` table with `slug = 'newprovider'` and your API key.
4. Run `populate-services.php` to sync their catalogue.

---

## Database tables

| Table | Purpose |
|-------|---------|
| `users` | User accounts |
| `wallets` | Each user's balance |
| `wallet_logs` | Full debit/credit audit trail |
| `providers` | VTU provider config (name, base_url, api_key) |
| `services` | Normalised service catalogue |
| `provider_services` | Maps services → provider's internal SKU + cost price |
| `transactions` | Every purchase attempt with full status history |
| `transaction_queue` | Async job queue consumed by the worker cron |
| `provider_callbacks` | Raw webhook payloads (audit log) |
| `notifications` | In-app user notifications |
| `apicache` | Cached provider product lists |
| `commissions` | Profit tracking per transaction |

---

## Security notes

- All purchase endpoints require a valid PHP session (login first).
- Idempotency keys prevent double-charging on network retries.
- Wallet updates use `FOR UPDATE` row locks to prevent race conditions.
- Webhook signatures are verified per-provider (see `webhooks/provider.php`).
- Never commit real API keys — use environment variables in production.
- All DB queries use PDO prepared statements — no raw SQL string interpolation.

---

## Providers supported

| Provider | Slug | Services |
|----------|------|----------|
| CheapDataHub | `cheapdatahub` | Airtime, Data, Electricity, Cable, Exam PINs |
| ConnectBridge | `connectbridge` | Airtime, Data (fallback) |

---

## License

MIT — free to use and modify.
