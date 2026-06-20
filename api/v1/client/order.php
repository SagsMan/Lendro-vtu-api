<?php
/**
 * POST /api/v1/client/order
 *
 * Place a VTU purchase order.
 *
 * The wallet is debited immediately and the actual provider call happens
 * asynchronously via the background worker — users never wait for slow APIs.
 *
 * Accepts both JSON body and application/x-www-form-urlencoded (legacy frontend).
 *
 * Request body:
 *   service_id       int     required — internal service ID (from /client/show or /client/services)
 *   phone            string  required — recipient's phone number
 *   idempotency_key  string  required — client UUID to prevent duplicate orders
 *   amount           float   optional — only needed for flexible-price services (airtime)
 *
 * Response:
 *   { "status": "processing", "reference": "LDR-xxx", "amount": 500, "message": "..." }
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../TransactionService.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
}

$userid = requireAuth();

// Accept both JSON body and URL-encoded form data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $body = $_POST;
}

// The frontend may send "serviceid" (old key) or "service_id" (new key)
$serviceId      = (int)   ($body['service_id']     ?? $body['serviceid']      ?? 0);
$phone          = trim(    $body['phone']           ?? $body['myphone']        ?? '');
$idempotencyKey = trim(    $body['idempotency_key'] ?? $body['idempotency']   ?? '');
$amount         = (float)  ($body['amount']         ?? 0);

// Auto-generate idempotency key if frontend didn't send one (graceful degradation)
if (empty($idempotencyKey)) {
    $idempotencyKey = 'auto_' . $userid . '_' . $serviceId . '_' . time();
}

// Normalise phone (strip leading 0 / country code)
$phone = toPhone10($phone);

// ── Validation ────────────────────────────────────────────────────────────────
if (!$serviceId) {
    sendJson(['status' => 'failed', 'message' => 'service_id is required.'], 422);
}
if (empty($phone) || strlen($phone) < 10) {
    sendJson(['status' => 'failed', 'message' => 'A valid 10-digit phone number is required.'], 422);
}

// ── Process the order ─────────────────────────────────────────────────────────
$result = TransactionService::process($userid, $serviceId, $phone, $idempotencyKey, $db, $amount);

$statusMap = ['processing' => 202, 'already_processed' => 200, 'failed' => 400];
$httpCode  = $statusMap[$result['status']] ?? 200;

sendJson($result, $httpCode);
