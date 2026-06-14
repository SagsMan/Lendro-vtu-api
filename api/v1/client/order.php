<?php
/**
 * POST /api/v1/client/order
 *
 * Place a VTU purchase order.
 *
 * The wallet is debited immediately; the actual provider request happens
 * asynchronously via the background worker. This means the user gets
 * an instant response and isn't left waiting for a slow provider API.
 *
 * Request body (JSON):
 *   service_id      int     required — internal service ID
 *   phone           string  required — recipient's phone number
 *   idempotency_key string  required — client-generated UUID to prevent duplicate orders
 *
 * Response:
 *   { "status": "processing", "reference": "LDR-xxx", "message": "..." }
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../TransactionService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
}

$userid = requireAuth();

$body           = json_decode(file_get_contents('php://input'), true) ?? [];
$serviceId      = (int)   ($body['service_id']      ?? $_POST['service_id']      ?? 0);
$phone          = trim(    $body['phone']            ?? $_POST['phone']           ?? '');
$idempotencyKey = trim(    $body['idempotency_key']  ?? $_POST['idempotency_key'] ?? '');

// ── Validation ────────────────────────────────────────────────────────────────
if (!$serviceId) {
    sendJson(['status' => 'failed', 'message' => 'service_id is required.'], 422);
}
if (empty($phone)) {
    sendJson(['status' => 'failed', 'message' => 'phone is required.'], 422);
}
if (empty($idempotencyKey)) {
    sendJson(['status' => 'failed', 'message' => 'idempotency_key is required. Generate one with crypto.randomUUID() on the frontend.'], 422);
}

// ── Process the order ─────────────────────────────────────────────────────────
$result = TransactionService::process($userid, $serviceId, $phone, $idempotencyKey, $db);

$httpCode = match ($result['status']) {
    'processing'       => 202,  // Accepted — background worker will handle it
    'already_processed'=> 200,
    'failed'           => 400,
    default            => 200,
};

sendJson($result, $httpCode);
