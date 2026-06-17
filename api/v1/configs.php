<?php
  /**
   * Lendro VTU API — Global Configuration
   */

  // ── CORS headers ─────────────────────────────────────────────────────────────
  header("Content-Type: application/json");
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Credentials: true");
  header("Access-Control-Allow-Headers: Content-Type, X-Idempotency-Key");
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      http_response_code(200);
      exit;
  }

  // ── Service markup ────────────────────────────────────────────────────────────
  define('MARKUP', 0.15);

  // ── App base URL ──────────────────────────────────────────────────────────────
  define('BASE_URL', 'https://lendro.trackd.live');
  define('BASE_DIR', __DIR__);

  // ── Active provider slugs ─────────────────────────────────────────────────────
  const PROVIDER_SLUGS = ['cheapdatahub', 'connectbridge'];

  // ── Database credentials ──────────────────────────────────────────────────────
  $host     = 'localhost';
  $dbname   = 'tracsmda_lendro';
  $username = 'tracsmda_lendro1';
  $password = 'LendroDb2024!';

  // ── Payment gateway (Squad — for wallet top-ups) ──────────────────────────────
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
  $CacheExpiryinHrs   = 96;
  $UPointPerPurchase  = 3;
  