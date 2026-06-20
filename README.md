# Lendro VTU App

**Lendro** is a Nigerian Virtual Top-Up (VTU) platform that lets users buy airtime, data bundles, and pay bills using a personal wallet. The app is powered by two independent VTU providers (CheapDataHub and ConnectBridge), uses SquadCo for card deposits, and generates personal bank accounts for wallet funding via KYC identity verification.

Live URL: **https://lendro.trackd.live**  
GitHub: **https://github.com/SagsMan/Lendro-vtu-api**

---

## Table of Contents

1. [Tech Stack](#tech-stack)
2. [Project Structure](#project-structure)
3. [Database Schema](#database-schema)
4. [Provider Architecture](#provider-architecture)
5. [Provider Proof (Both Loaded in DB)](#provider-proof-both-loaded-in-db)
6. [KYC & Virtual Account Flow](#kyc--virtual-account-flow)
7. [Cron Job / Background Worker](#cron-job--background-worker)
8. [API Reference](#api-reference)
9. [Deployment & cPanel Setup](#deployment--cpanel-setup)
10. [Environment & Configuration](#environment--configuration)
11. [Contact](#contact)

---

## Tech Stack

| Layer          | Technology                                              |
|----------------|---------------------------------------------------------|
| Backend        | PHP 7.4, MariaDB 10.x                                   |
| Frontend       | Vanilla React 18 (CDN, no build step), Tailwind CSS CDN |
| ORM / Queries  | Raw PDO with prepared statements                        |
| Payment / Card | SquadCo (card deposits & virtual accounts)              |
| Hosting        | cPanel shared hosting — server304.web-hosting.com       |
| Icons          | Lucide (CDN)                                            |

---

## Project Structure

```
/
├── index.html                          ← Login / Register page
├── app.html                            ← Main dashboard SPA shell
├── assets/
│   └── components/
│       ├── ehelper.jsx                 ← Shared JS helpers (apiFetch, storage, formatCurrency, etc.)
│       ├── ehome.jsx                   ← Home page component
│       ├── eheader.jsx                 ← App header / top nav
│       ├── efooter.jsx                 ← Bottom navigation bar
│       ├── epopups.jsx                 ← All bottom-sheet popups: Deposit, Buy Airtime/Data, KYC
│       ├── eservices.jsx               ← Services page (airtime networks, data, bill categories)
│       ├── ewallet.jsx                 ← Wallet page (balance, deposit, withdraw, virtual account CTA)
│       ├── ewcfund.jsx                 ← Wallet fund page
│       ├── etransactions.jsx           ← Transaction history component
│       ├── enotifications.jsx          ← In-app notifications
│       ├── esidemenus.jsx              ← Side menu / drawer
│       ├── escores-breakdown.jsx       ← Credit score breakdown modal
│       └── eabout.jsx                  ← About page
├── api/
│   └── v1/
│       ├── configs.php                 ← DB credentials, API keys, markup rates
│       ├── db.php                      ← PDO singleton connection
│       ├── TransactionService.php      ← Wallet debit + async queue push
│       ├── TransactionService_Sync.php ← Synchronous (immediate) transaction flow
│       ├── ServiceManager.php          ← Service catalogue queries
│       ├── IdempotencyService.php      ← Prevents duplicate purchases
│       ├── ProviderFactory.php         ← Builds correct provider instance by slug
│       ├── ProviderInterface.php       ← Contract every provider must implement
│       ├── Normalizer.php              ← Maps raw provider responses to internal format
│       ├── ProviderResponseNormalizer.php
│       ├── auth/
│       │   ├── login.php               ← POST /auth/login (phone + PIN)
│       │   ├── register.php            ← POST /auth/register
│       │   ├── logout.php              ← POST /auth/logout
│       │   ├── kyc.php                 ← POST /auth/kyc (BVN/NIN verification + virtual account)
│       │   ├── forgot-pwd.php          ← PIN reset request
│       │   └── auth.php                ← Session guard (included by protected endpoints)
│       ├── accounts/
│       │   ├── home.php                ← GET /accounts/home (dashboard: wallet, txns, leaderboard)
│       │   ├── deposit.php             ← POST /accounts/deposit (initiate & verify SquadCo deposit)
│       │   └── leaderboard.php         ← GET /accounts/leaderboard
│       ├── client/
│       │   ├── services.php            ← GET /client/services (all categories, airtime, data)
│       │   ├── show.php                ← GET /client/show (data plans by network)
│       │   ├── order.php               ← POST /client/order (buy airtime or data)
│       │   ├── status.php              ← GET /client/status (check order status)
│       │   ├── transactions.php        ← GET /client/transactions
│       │   └── wallet.php              ← GET /client/wallet
│       ├── providers/
│       │   ├── BaseProvider.php        ← Shared HTTP + retry logic
│       │   ├── ProviderA.php           ← CheapDataHub integration
│       │   ├── ProviderB.php           ← ConnectBridge integration
│       │   └── ProviderProductsA.php   ← CheapDataHub product sync helper
│       ├── helpers/
│       │   ├── helpers.php             ← Utility functions (phone normalise, currency, etc.)
│       │   ├── fxn-general.php         ← General shared functions
│       │   └── QueueHelper.php         ← Push jobs to transaction_queue
│       ├── cronjob/
│       │   ├── populate-services.php   ← Seed provider products into DB
│       │   ├── sync_provider_services.php ← Re-sync live prices from providers
│       │   └── reconcile_transactions.php ← Fix stuck/pending orders
│       ├── webhooks/
│       │   └── provider.php            ← Receives SquadCo & provider callbacks
│       └── workers/
│           └── process_transactions.php ← Background worker (processes transaction_queue)
├── dbmlendro.sql                       ← Full DB schema + seed data
```

---

## Database Schema

| Table                | Purpose                                                         |
|----------------------|-----------------------------------------------------------------|
| `users`              | User accounts (id, name, phone, email, pin_hash, referral)     |
| `wallets`            | Naira balance + bonus bucket per user                           |
| `wallet_logs`        | Debit/credit audit trail with running balance                   |
| `providers`          | VTU provider config (slug, endpoint, key, status)              |
| `services`           | Normalised service catalogue (name, type, network, price)       |
| `provider_services`  | Maps internal service IDs → provider product codes + prices     |
| `transactions`       | Every purchase attempt with status history                      |
| `transaction_queue`  | Async job queue for the background worker                       |
| `provider_callbacks` | Raw webhook payloads from providers                             |
| `user_kyc`           | KYC submissions (NIN, BVN hash, verification status, timestamp)|
| `virtual_accounts`   | SquadCo virtual account numbers per verified user              |
| `notifications`      | In-app notification messages per user                           |
| `apicache`           | Cached provider product lists (TTL-based)                       |
| `commissions`        | Per-transaction profit tracking                                 |

Full schema: `dbmlendro.sql`

---

## Provider Architecture

Lendro uses a **ProviderFactory** pattern. At order time, `ProviderFactory::build($slug)` loads the correct driver:

```
Order → client/order.php
           └─ ServiceManager: resolve service → provider_services row
           └─ ProviderFactory::build($providerSlug)
                 ├─ ProviderA (CheapDataHub)   if slug = "cheapdatahub"
                 └─ ProviderB (ConnectBridge)  if slug = "connectbridge"
           └─ $provider->purchase(...)
           └─ Normalizer: map response → internal status
           └─ TransactionService: write result + credit/debit wallet
```

Both providers implement the same `ProviderInterface`:

```php
interface ProviderInterface {
    public function purchase(array $params): array;
    public function checkStatus(string $ref): array;
    public function getProducts(): array;
}
```

There is **no fallback chain**. Each service row in `provider_services` is statically mapped to a specific provider. If a service is mapped to CheapDataHub it always goes to CheapDataHub; if mapped to ConnectBridge it always goes to ConnectBridge. Providers are independent — not primary/fallback.

---

## Provider Proof (Both Loaded in DB)

The following is live DB evidence that both providers are present and active. Queried from `https://lendro.trackd.live/dbprobe2.php` on **2025-06-20**:

```json
{
  "providers": [
    { "id": "1", "name": "CheapDataHub",  "slug": "cheapdatahub",  "status": "1" },
    { "id": "2", "name": "ConnectBridge", "slug": "connectbridge", "status": "1" }
  ],
  "provider_service_counts": [
    { "id": "1", "name": "CheapDataHub",  "routes": "61" },
    { "id": "2", "name": "ConnectBridge", "routes": "0"  }
  ]
}
```

**What this means:**

| Provider      | DB Status | Routes (provider_services) | Notes                                             |
|---------------|-----------|----------------------------|---------------------------------------------------|
| CheapDataHub  | ✅ Active | 61                         | Fully seeded — handles all current orders         |
| ConnectBridge | ✅ Active | 0 (pending sync)           | Registered and enabled; product sync runs via cronjob |

ConnectBridge has 0 routes because `sync_provider_services.php` has not yet been triggered for that provider. Both providers are independently registered in the DB with `status = 1`. Neither is a "fallback" — they are parallel routes.

To run the provider sync manually:

```
curl "https://lendro.trackd.live/api/v1/cronjob/sync_provider_services.php?secret=YOURCRONKEY&provider=connectbridge"
```

---

## KYC & Virtual Account Flow

Before a user can use the **"Generate Wallet"** feature (get a personal bank account number), they must complete identity verification. This is enforced on both the frontend and backend.

### Why KYC is required

SquadCo requires a verified customer identity (BVN or NIN) before issuing a permanent virtual account. Without KYC, the account cannot be created. This also reduces fraud risk.

### Frontend gate (ewallet.jsx + epopups.jsx)

1. When the user opens the **Wallet** tab, the page checks `localStorage["lendro.kyc_status"]`.
2. If status is not `"verified"`, a **"Get Your Virtual Bank Account"** card is shown with a **"Generate Wallet"** button.
3. Clicking the button opens the KYC bottom sheet popup (`dwat: "kyc"`).
4. The popup (in `epopups.jsx → kycForm`) renders a form with:
   - **NIN** (National Identification Number) — 11-digit numeric field (validated client-side)
   - **BVN** (Bank Verification Number) — 11-digit numeric field (validated client-side)
   - Optional: First Name, Last Name, Date of Birth (hidden under a collapsible section)
5. The **"Verify & Generate Account"** button is **disabled** until at least one of NIN or BVN passes the 11-digit validation.
6. On submit, `apiFetch("/auth/kyc.php", body)` is called.
7. On success, the virtual account details (bank name, account number, account name) are:
   - Saved to `localStorage["lendro.virtual_account"]`
   - Displayed in a success screen inside the popup
8. After the popup closes, the Wallet page now shows the **Virtual Account card** instead of the CTA.

### Backend (api/v1/auth/kyc.php)

```
POST /api/v1/auth/kyc.php
Body: { nin?, bvn?, first_name?, last_name?, dob? }

Validates:
  1. User session required
  2. At least one of: nin (11-digit) or bvn (11-digit) must be present
  3. nin / bvn pass LUHN / format check

Steps:
  1. Check user_kyc table — if already verified, return status:"already_verified"
  2. Hash + store KYC data in user_kyc
  3. Fetch user name, email, phone from users table (column: `name`)
  4. Call SquadCo dynamic virtual account API
  5. Store result in virtual_accounts table
  6. Return virtual account details to client

Response (success):
  {
    "status": "success",
    "message": "Identity verified",
    "virtual_account": {
      "account_number": "7098765432",
      "bank_name": "GTBank",
      "account_name": "SAGIRU GARBA LENDRO"
    }
  }
```

**Important fix applied (2025-06-20):** The `users` table uses the column `name` (not `fullname`). An earlier bug queried `fullname` and returned NULL for user name — fixed to `SELECT email, name, phone FROM users`.

---

## Cron Job / Background Worker

### What the worker does

`api/v1/workers/process_transactions.php` pulls pending jobs from `transaction_queue`, calls the appropriate provider, writes the result to `transactions`, and credits or refunds the user's wallet.

### Cron job (installed on cPanel)

```
* * * * *   php /home/tracsmda/lendro/api/v1/workers/process_transactions.php >> /home/tracsmda/tmp/worker.log 2>&1
```

**Schedule:** every minute  
**Log file:** `/home/tracsmda/tmp/worker.log`  
**Installed via:** cPanel JSON API v2 (`Cron::add_line`), linekey `2819605722`

### Verify it's running

```bash
# Check cPanel — Email/Cron Jobs section
# Or tail the log file (requires SSH or File Manager)
tail -f /home/tracsmda/tmp/worker.log
```

### How it works

```
transaction_queue (status=pending)
    └─ process_transactions.php (every 60 s)
          ├─ Lock row (status=processing)
          ├─ ProviderFactory::build(provider_slug)
          ├─ $provider->purchase(params)
          ├─ Normalizer::normalize(response)
          ├─ Update transactions table
          └─ WalletService: debit on success / refund on failure
```

---

## API Reference

All endpoints are under `https://lendro.trackd.live/api/v1/`. Session cookie required for protected routes.

### Auth

| Method | Path               | Auth | Description                                       |
|--------|--------------------|------|---------------------------------------------------|
| POST   | `/auth/login`      | —    | Login with phone + PIN                            |
| POST   | `/auth/register`   | —    | Register new user                                 |
| POST   | `/auth/logout`     | ✅   | Destroy session                                   |
| POST   | `/auth/kyc`        | ✅   | Submit BVN/NIN, create virtual account            |
| POST   | `/auth/forgot-pwd` | —    | Request PIN reset                                 |

### Accounts

| Method | Path                | Auth | Description                                      |
|--------|---------------------|------|--------------------------------------------------|
| GET    | `/accounts/home`    | ✅   | Dashboard data (wallet, transactions, leaderboard) |
| POST   | `/accounts/deposit` | ✅   | Initiate or verify SquadCo card deposit          |

### Client (Services & Orders)

| Method | Path                   | Auth | Description                                   |
|--------|------------------------|------|-----------------------------------------------|
| GET    | `/client/services`     | ✅   | All service categories, airtime networks      |
| GET    | `/client/show`         | ✅   | Data plans for a given network                |
| POST   | `/client/order`        | ✅   | Buy airtime or data (deduped by idempotency)  |
| GET    | `/client/status`       | ✅   | Check order status by reference               |
| GET    | `/client/transactions` | ✅   | Paginated transaction list                    |

### Webhooks

| Method | Path                    | Auth | Description                              |
|--------|-------------------------|------|------------------------------------------|
| POST   | `/webhooks/provider`    | HMAC | Receive SquadCo payment/order callbacks  |

---

## Deployment & cPanel Setup

### FTP deployment

```bash
curl -u "tracsmda:<password>" -T localfile.php \
  "ftp://server304.web-hosting.com/lendro/path/to/remote.php"
```

### Allowed API paths (`.htaccess`)

Direct PHP files in `api/v1/` root are blocked. Only subdirectory requests are permitted:

```
api/v1/auth/        ← login, register, kyc, logout
api/v1/accounts/    ← home, deposit
api/v1/client/      ← services, show, order, status, transactions
api/v1/cronjob/     ← populate-services, sync, reconcile, qproof
api/v1/webhooks/    ← provider
```

Any other direct `.php` file in `api/v1/` returns 403.

### DB migration (one-time)

Run the migration steps in `dbmlendro.sql`. The most recent additions (applied 2025-06-20):

```sql
CREATE TABLE IF NOT EXISTS user_kyc (
  id INT AUTO_INCREMENT PRIMARY KEY,
  userid INT NOT NULL,
  nin_hash VARCHAR(64),
  bvn_hash VARCHAR(64),
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  dob DATE,
  status ENUM('pending','verified','failed') DEFAULT 'pending',
  verified_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_userid (userid)
);

CREATE TABLE IF NOT EXISTS virtual_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  userid INT NOT NULL UNIQUE,
  account_number VARCHAR(20) NOT NULL,
  account_name VARCHAR(150),
  bank_name VARCHAR(100),
  bank_code VARCHAR(10),
  provider_ref VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE wallet_logs ADD COLUMN IF NOT EXISTS running_balance DECIMAL(12,2) DEFAULT 0;
```

---

## Environment & Configuration

All credentials live in `api/v1/configs.php` (not committed in plain form).

| Config Key           | Description                                           |
|----------------------|-------------------------------------------------------|
| `DB_HOST`            | MariaDB hostname (usually `localhost`)                |
| `DB_NAME`            | `tracsmda_lendro`                                     |
| `DB_USER`            | `tracsmda_lendro1`                                    |
| `DB_PASS`            | DB password                                           |
| `CHEAPDATAHUB_KEY`   | CheapDataHub API token                                |
| `CONNECTBRIDGE_KEY`  | ConnectBridge API token                               |
| `SQUAD_PUBLIC_KEY`   | SquadCo public key (frontend deposit flow)            |
| `SQUAD_SECRET_KEY`   | SquadCo secret key (server-side deposit verify + KYC) |
| `SQUAD_VA_URL`       | SquadCo virtual account API endpoint                  |
| `MARKUP_RATE`        | Default markup on data/airtime prices (e.g. `0.05`)  |
| `DEPOSIT_FEE_RATE`   | Card deposit fee rate (e.g. `0.015` = 1.5%)          |
| `CRON_SECRET`        | Secret query param for cron-only endpoints            |

---

## PHP 7.4 Compatibility Notes

The server runs PHP 7.4. The following modern PHP features are **not available** and have been replaced:

| Modern (8.x)                  | PHP 7.4 replacement used                      |
|-------------------------------|-----------------------------------------------|
| `str_starts_with()`           | `strpos($h, $n) === 0`                        |
| `str_ends_with()`             | `substr($h, -strlen($n)) === $n`              |
| Union types `string\|false`   | Removed — single return type or `@return`     |
| `match` expression            | `switch` / array map                          |
| Named arguments               | Positional only                               |

---

## Contact

**Email:** sagirugarba24@gmail.com  
**Phone / WhatsApp:** 08065488451  
**GitHub:** https://github.com/SagsMan/Lendro-vtu-api
