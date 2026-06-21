# Lendro VTU App

Mobile-first PHP + React web app for Nigerian VTU (Virtual Top-Up) services. Users buy Airtime, Data, Cable TV, Electricity tokens, and Exam PINs using a pre-funded wallet.

**Live URL:** https://lendro.trackd.live  
**GitHub:** https://github.com/SagsMan/Lendro-vtu-api

---

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ (no framework) |
| Frontend | React (no bundler — `React.createElement` via CDN) |
| Database | MySQL (cPanel) |
| Payments | SquadCo (wallet funding) |
| VTU Provider A | CheapDataHub — airtime, data, electricity, cable, education |
| VTU Provider B | ConnectBridge — airtime, data, cable, electricity, education |

---

## Architecture

```
lendro/
├── app.html                        # Single-page app shell (loads all JS components)
├── assets/
│   ├── components/
│   │   ├── ehelper.jsx             # Global config: icons, colours, categoryBillers, categoryTitles
│   │   ├── ehome.jsx               # Home page component
│   │   ├── eservices.jsx           # Partner Services page + ServicesOnHome widget
│   │   ├── epopups.jsx             # All purchase forms + Buy() function + wallet auto-refresh
│   │   └── ...
│   └── images/                     # Network logos (mtn.jpg, airtel.jpg, glo.jpg, 9mobile.jpg …)
└── api/v1/
    ├── configs.php                 # DB credentials, markup constants
    ├── db.php                      # PDO singleton + session start + helpers include
    ├── helpers/helpers.php         # getAllServices(), requireAuth(), generateRefNo() …
    ├── providers/
    │   ├── BaseProvider.php        # HTTP request() + auth handling (Bearer / Token)
    │   ├── ProviderA.php           # CheapDataHub integration
    │   ├── ProviderB.php           # ConnectBridge integration
    │   ├── ProviderProductsA.php   # Static CheapDataHub product catalogue
    │   └── ProviderResponseNormalizer.php
    ├── client/
    │   ├── order.php               # POST /api/v1/client/order  — place purchase
    │   ├── wallet.php              # GET  /api/v1/client/wallet — balance + transactions
    │   ├── verify.php              # POST /api/v1/client/verify — smart card / meter lookup
    │   ├── show.php                # GET  /api/v1/client/show   — data plan list for a network
    │   └── services.php            # GET  /api/v1/client/services — all grouped services
    ├── services/index.php          # Alias for client/services (used by frontend)
    ├── TransactionService.php      # Wallet debit + provider dispatch + DB write
    └── workers/                    # Background job that actually calls provider APIs
```

---

## Provider A — CheapDataHub

**Base URL:** configured in `ProviderProductsA.php`  
**Auth:** `apikey` field in request body

### Airtime Purchase

**Request** `POST /airtime/purchase/`
```json
{
  "apikey":       "<CHEAPDATAHUB_KEY>",
  "provider_id":  "mtn",
  "phone_number": "08012345678",
  "amount":       500,
  "request_id":   "LDR-1718400000-abc123"
}
```

**Response (success)**
```json
{
  "status":  "success",
  "message": "Airtime topped up successfully",
  "data": {
    "reference":  "LDR-1718400000-abc123",
    "network":    "mtn",
    "amount":     500,
    "phone":      "08012345678"
  }
}
```

### Data Purchase

**Request** `POST /data/purchase/`
```json
{
  "apikey":       "<CHEAPDATAHUB_KEY>",
  "bundle_id":    "42",
  "phone_number": "08012345678",
  "request_id":   "LDR-1718400000-def456"
}
```

**Response (success)**
```json
{
  "status":  "success",
  "message": "Data bundle activated",
  "data": {
    "reference": "LDR-1718400000-def456",
    "network":   "mtn",
    "plan":      "1GB 30 Days"
  }
}
```

### Electricity Purchase

**Request** `POST /electricity/purchase/`
```json
{
  "apikey":        "<CHEAPDATAHUB_KEY>",
  "disco_id":      "AEDC",
  "meter_number":  "12345678901",
  "amount":        2000,
  "meter_type":    "prepaid",
  "phone_number":  "08012345678",
  "request_id":    "LDR-1718400000-ghi789"
}
```

**Response (success)**
```json
{
  "status":  "success",
  "message": "Token generated",
  "data": {
    "token":          "1234-5678-9012-3456",
    "units":          "23.4 kWh",
    "meter_number":   "12345678901",
    "reference":      "LDR-1718400000-ghi789"
  }
}
```

### Cable TV Purchase

**Request** `POST /cable/purchase/`
```json
{
  "apikey":       "<CHEAPDATAHUB_KEY>",
  "plan_id":      "dstv-compact",
  "cardnumber":   "1234567890",
  "phone":        "08012345678",
  "request_id":   "LDR-1718400000-jkl012"
}
```

**Response (success)**
```json
{
  "status":  "success",
  "message": "DSTv Compact subscription renewed",
  "data": {
    "smartcard":   "1234567890",
    "plan":        "DSTv Compact",
    "reference":   "LDR-1718400000-jkl012"
  }
}
```

### Education / Exam PIN Purchase

**Request** `POST /exam-pin/purchase/`
```json
{
  "apikey":      "<CHEAPDATAHUB_KEY>",
  "product_id":  "waec",
  "quantity":    1,
  "request_id":  "LDR-1718400000-mno345"
}
```

**Response (success)**
```json
{
  "status":  "success",
  "message": "WAEC PIN purchased",
  "data": {
    "pin":       "1234-5678-9012",
    "serial":    "SER12345678",
    "reference": "LDR-1718400000-mno345"
  }
}
```

---

## Provider B — ConnectBridge

**Base URL:** `https://connectbridge.com.ng`  
**Auth:** `Authorization: Token <API_KEY>` header  
**DB:** `auth_mode = 'token'` in the `providers` table

### Airtime Purchase

**Request** `POST /api/airtime`
```json
{
  "network":        "mtn",
  "mobile_number":  "08012345678",
  "amount":         500,
  "request_id":     "LDR-1718400000-abc123"
}
```

**Response (success)**
```json
{
  "status":    "success",
  "message":   "Airtime purchase successful",
  "reference": "LDR-1718400000-abc123",
  "data": {
    "network": "mtn",
    "amount":  500,
    "phone":   "08012345678"
  }
}
```

### Data Purchase

**Request** `POST /api/data`
```json
{
  "network":        "mtn",
  "plan":           "mtn-1gb-30days",
  "mobile_number":  "08012345678",
  "bypass":         0,
  "request_id":     "LDR-1718400000-def456"
}
```

**Response (success)**
```json
{
  "status":    "success",
  "message":   "Data purchase successful",
  "reference": "LDR-1718400000-def456",
  "data": {
    "network": "mtn",
    "plan":    "1GB — 30 Days"
  }
}
```

### Electricity Purchase

**Request** `POST /api/electricity`
```json
{
  "disco_name":    "AEDC",
  "meter_number":  "12345678901",
  "meter_type":    "prepaid",
  "amount":        2000,
  "phone":         "08012345678",
  "request_id":    "LDR-1718400000-ghi789"
}
```

**Response (success)**
```json
{
  "status":    "success",
  "message":   "Electricity token generated",
  "reference": "LDR-1718400000-ghi789",
  "data": {
    "token":         "1234-5678-9012-3456",
    "units":         "23.4 kWh",
    "meter_number":  "12345678901"
  }
}
```

### Cable TV Purchase

**Request** `POST /api/cable`
```json
{
  "cable_name":        "dstv",
  "smartcard_number":  "1234567890",
  "plan_id":           "dstv-compact",
  "phone":             "08012345678",
  "request_id":        "LDR-1718400000-jkl012"
}
```

**Response (success)**
```json
{
  "status":    "success",
  "message":   "Cable subscription successful",
  "reference": "LDR-1718400000-jkl012",
  "data": {
    "smartcard": "1234567890",
    "plan":      "DSTv Compact"
  }
}
```

### Education / Exam PIN Purchase

**Request** `POST /api/education`
```json
{
  "exam_body":    "waec",
  "quantity":     1,
  "phone":        "08012345678",
  "request_id":   "LDR-1718400000-mno345"
}
```

**Response (success)**
```json
{
  "status":    "success",
  "message":   "Exam PIN purchased",
  "reference": "LDR-1718400000-mno345",
  "data": {
    "pin":    "1234-5678-9012",
    "serial": "SER12345678"
  }
}
```

### Smart Card / Meter Verification

**Request** `POST /api/query` (or `/api/verify`)
```json
{
  "type":          "cable",
  "provider":      "dstv",
  "smartcard_no":  "1234567890"
}
```

**Response (success)**
```json
{
  "status":       "success",
  "customer_name": "JOHN DOE",
  "account_no":    "1234567890",
  "current_plan":  "DSTv Compact"
}
```

---

## Key Frontend Flows

### Home — Partner Services Widget
Always shows **4 hardcoded icons** (no DB dependency):
`Airtime` → `Data` → `Cable TV` → `Education` + `More`

### Services Page
- **Airtime Recharge**: DB networks if seeded, else static MTN / Airtel / GLO / 9mobile fallback
- **Data Bundle**: DB networks if seeded, else static MTN / Airtel / GLO / 9mobile-data fallback (all 4)
- **Other Services**: Cable TV + Electricity + Education from DB categories

### Purchase Flow
1. User taps service icon → popup form opens
2. For Cable TV / Electricity: **Verify** button calls `POST /api/v1/client/verify` → shows customer name card
3. User fills amount/phone → taps **Pay**
4. `Buy()` calls `POST /api/v1/client/order` → wallet debited instantly
5. Background worker calls Provider A or B
6. Wallet balance **auto-refreshes** after every successful purchase via `GET /api/v1/client/wallet`

---

## API Endpoints Reference

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/client/services` | All grouped services (airtime, data, categories) |
| POST | `/api/v1/client/order` | Place a purchase order |
| GET | `/api/v1/client/wallet` | Wallet balance + recent transactions |
| POST | `/api/v1/client/verify` | Verify smart card / meter number |
| GET | `/api/v1/client/show` | Data plans for a specific network |
| GET | `/api/v1/client/transactions` | Transaction history |

---

## Deployment

**Live server:** cPanel at `server304.web-hosting.com`, doc root `/home/tracsmda/lendro`  
**Upload via FTP:**
```bash
curl --ftp-ssl "ftp://server304.web-hosting.com/lendro/<path>" \
  -u "tracsmda:<pass>" -T <localfile>
```

**DB migration:**
```bash
# api/v1/database/add_provider_auth_mode.sql
ALTER TABLE providers ADD COLUMN IF NOT EXISTS auth_mode VARCHAR(20) DEFAULT 'bearer';
UPDATE providers SET auth_mode='token' WHERE slug='connectbridge';
```

---

## Changes Log

| Date | Summary |
|---|---|
| 2026-06-20 | Initial full deployment: both providers, wallet, services, education |
| 2026-06-20 | Home Partner Services: 4 hardcoded icons (Airtime/Data/Cable/Education) always visible |
| 2026-06-20 | Services page: static network fallbacks for Airtime and Data (including 9mobile) |
| 2026-06-20 | Page titles fixed: "Electricity", "Cable TV", "Education" (not DISCO bill names) |
| 2026-06-20 | Smart card/meter Verify step before Cable TV / Electricity purchase |
| 2026-06-20 | ConnectBridge Token auth (`Authorization: Token`) |
| 2026-06-20 | Wallet balance auto-refreshes after every successful purchase |

---

## ConnectBridge — No Product Listing API (Important)

ConnectBridge does **not** expose a product-listing endpoint. `ProviderB::getServices()` returns `[]` by design. The `populate-services` cronjob skips it with `"No products returned"` — this is expected.

**Fix:** A static catalogue is seeded into the DB via:
```
GET https://lendro.trackd.live/api/v1/client/seed-connectbridge.php?key=seed2024
```

This script:
1. Pings ConnectBridge `/api/user` and shows HTTP status + raw response
2. Shows service count before and after seed
3. Inserts 27 static services (airtime × 4 networks, data × 9 plans, cable × 5 plans, electricity × 5 DISCOs, education × 4 bodies) and links them to ConnectBridge in `provider_services`

Run it once after deployment. Re-running is safe (skips existing rows).

---

## Changelog (continued)

| Date | Summary |
|---|---|
| 2026-06-21 | Electricity added to Home Partner Services (now 5 icons in 2-row grid) |
| 2026-06-21 | Cancel from cable/electricity popup returns to plan grid (billercode in local state) |
| 2026-06-21 | "Loading plans…" hidden on static biller pages (cable/electricity/education) |
| 2026-06-21 | SquadCo deposit: catch still calls VerifyFund (fixes "success but shows failed") |
| 2026-06-21 | Wallet balance refreshes immediately after every deposit/purchase |
| 2026-06-21 | AEDC / NECO / WAEC / BEDC logos uploaded and used in biller grid |
| 2026-06-21 | Markup updated: MARKUP_1K=₦20, MARKUP_25K=₦50, MARKUP_MAX=₦100, MARKUP_STEP=₦10 |
| 2026-06-21 | ConnectBridge seed script seeds 27 services into DB (no listing API workaround) |
