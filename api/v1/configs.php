<?php
/**
 * Lendro VTU API — Global Configuration
 *
 * All environment-level constants live here.
 * In production, move credentials to environment variables or a secrets manager.
 */

// ── CORS headers ─────────────────────────────────────────────────────────────
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");          // restrict to your domain in production
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, X-Idempotency-Key");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Service markup ────────────────────────────────────────────────────────────
// 15 % added on top of every provider's cost price before we store it as our selling price
define('MARKUP', 0.15);

// ── App base URL ──────────────────────────────────────────────────────────────
define('BASE_URL', 'https://yourdomain.com');      // change to your real domain
define('BASE_DIR', __DIR__);

// ── Active provider slugs ─────────────────────────────────────────────────────
// These must match the `slug` column in the `providers` database table
const PROVIDER_SLUGS = ['cheapdatahub', 'connectbridge'];

// ── Database credentials ──────────────────────────────────────────────────────
$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_NAME')     ?: 'dbmlendro';
$username = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASS')     ?: '';

// ── Payment gateway (Squad — for wallet top-ups) ──────────────────────────────
// Swap with live keys before going to production
$squard_SK       = getenv('SQUAD_SK')       ?: 'sandbox_sk_xxxxxxxxxxxxxxx';
$squard_PK       = getenv('SQUAD_PK')       ?: 'sandbox_pk_xxxxxxxxxxxxxxx';
$squard_Endpoint = getenv('SQUAD_ENDPOINT') ?: 'https://sandbox-api-d.squadco.com';
$squard_Merchant = getenv('SQUAD_MERCHANT') ?: 'SBX2EMWXDF';

// ── VTPass (alternative bill-payment gateway) ─────────────────────────────────
$G_PKEY     = getenv('VTPASS_PK')     ?: 'PK_xxxxxxxxxxxxxxx';
$G_SKEY     = getenv('VTPASS_SK')     ?: 'SK_xxxxxxxxxxxxxxx';
$G_APIKEY   = getenv('VTPASS_APIKEY') ?: 'xxxxxxxxxxxxxxx';
$G_Endpoint = getenv('VTPASS_URL')    ?: 'https://sandbox.vtpass.com/api';

// ── Misc app settings ─────────────────────────────────────────────────────────
$CurrencySymbol     = '₦';
$CacheExpiryinHrs   = 96;           // how long we cache provider product lists (4 days)
$UPointPerPurchase  = 3;            // usage-points credited per successful purchase
