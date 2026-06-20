<?php
/**
 * Lendro VTU API — Global Configuration
 */

// ── CORS headers ─────────────────────────────────────────────────────────────
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, X-Idempotency-Key, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Service markup ────────────────────────────────────────────────────────────
define('MARKUP', 0.15);         // 15% markup on provider cost price

// ── App base URL ──────────────────────────────────────────────────────────────
define('BASE_URL', 'https://lendro.trackd.live');
define('BASE_DIR', __DIR__);

// ── Active provider slugs ─────────────────────────────────────────────────────
const PROVIDER_SLUGS = ['cheapdatahub', 'connectbridge'];

// ── Database credentials ──────────────────────────────────────────────────────
$host     = 'localhost';
$dbname   = 'tracsmda_lendro';
$username = 'tracsmda_lendrou';
$password = 'Lendro@Secure2024';

// ── Payment gateway (Squad — for wallet top-ups) ──────────────────────────────
//
// Set these as environment variables in cPanel (Software > Setup PHP INI or
// .htaccess SetEnv) to switch between sandbox and live.
//
// Live:    SQUAD_ENDPOINT = https://api-d.squadco.com
//          SQUAD_SK       = sk_live_xxxx  (from Squad dashboard)
//          SQUAD_PK       = pk_live_xxxx
//          SQUAD_MERCHANT = your_merchant_id
//
// Sandbox: SQUAD_ENDPOINT = https://sandbox-api-d.squadco.com
//          SQUAD_SK       = sandbox_sk_xxxx
//
$squard_SK       = getenv('SQUAD_SK')       ?: '';
$squard_PK       = getenv('SQUAD_PK')       ?: '';
$squard_Endpoint = getenv('SQUAD_ENDPOINT') ?: 'https://api-d.squadco.com';  // default LIVE
$squard_Merchant = getenv('SQUAD_MERCHANT') ?: '';

// Alias used in deposit.php
$squad_SecretKey = $squard_SK;

// ── Deposit / withdrawal fee (percentage, e.g. 0.015 = 1.5%) ─────────────────
$DWFee = 0.015;

// ── VTPass (alternative bill-payment gateway) ─────────────────────────────────
$G_PKEY     = getenv('VTPASS_PK')     ?: '';
$G_SKEY     = getenv('VTPASS_SK')     ?: '';
$G_APIKEY   = getenv('VTPASS_APIKEY') ?: '';
$G_Endpoint = getenv('VTPASS_URL')    ?: 'https://vtpass.com/api';

// ── Misc app settings ─────────────────────────────────────────────────────────
$CurrencySymbol    = '₦';
$CacheExpiryinHrs  = 96;
$UPointPerPurchase = 3;
