<?php
/**
 * POST /api/v1/client/verify
 *
 * Verify a smart card (cable TV) or meter number (electricity)
 * before a purchase is made, so users can confirm their details.
 *
 * Request body:
 *   type            string  required — "cable" | "electricity"
 *   provider        string  required — e.g. "dstv", "gotv", "startimes", "AEDC", "EKEDC"
 *   smartcard       string  required for cable    — IUC / smart card number
 *   meter_number    string  required for electric — meter number
 *   meter_type      string  optional for electric — "prepaid" | "postpaid" (default prepaid)
 *
 * Response (success):
 *   { status: "success", data: { name: "JOHN DOE", address: "...", package: "..." } }
 *
 * Response (failure):
 *   { status: "failed", message: "..." }
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../ProviderFactory.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
}

$userid = requireAuth();

// Accept JSON or form-encoded
$ct   = $_SERVER['CONTENT_TYPE'] ?? '';
$body = stripos($ct, 'application/json') !== false
      ? (json_decode(file_get_contents('php://input'), true) ?? [])
      : $_POST;

$type       = strtolower(trim($body['type']         ?? ''));
$provider   = trim($body['provider']                ?? '');
$smartcard  = trim($body['smartcard']               ?? $body['smartcard_number'] ?? '');
$meterNo    = trim($body['meter_number']             ?? $body['meter_number']    ?? '');
$meterType  = strtolower(trim($body['meter_type']   ?? 'prepaid'));

// ── Validation ────────────────────────────────────────────────────────────────
if (!in_array($type, ['cable', 'electricity'])) {
    sendJson(['status' => 'failed', 'message' => 'type must be "cable" or "electricity".'], 422);
}
if (!$provider) {
    sendJson(['status' => 'failed', 'message' => 'provider is required.'], 422);
}
if ($type === 'cable' && !$smartcard) {
    sendJson(['status' => 'failed', 'message' => 'smartcard number is required for cable verification.'], 422);
}
if ($type === 'electricity' && !$meterNo) {
    sendJson(['status' => 'failed', 'message' => 'meter_number is required for electricity verification.'], 422);
}

// ── Call ConnectBridge /api/verify ────────────────────────────────────────────
try {
    $provider_obj = ProviderFactory::make('connectbridge', $db);

    if ($type === 'cable') {
        $payload = [
            'cable_name'     => $provider,
            'smartcard_number' => $smartcard,
        ];
    } else {
        $payload = [
            'disco_name'   => $provider,
            'meter_number' => $meterNo,
            'meter_type'   => $meterType,
        ];
    }

    // ProviderB exposes a generic request() — call via the purchase helper indirection
    // Use reflection to call the protected request() method from BaseProvider
    $ref    = new ReflectionMethod($provider_obj, 'request');
    $ref->setAccessible(true);
    $result = $ref->invoke($provider_obj, '/api/verify', $payload, 'POST');

    // Normalise provider response — ConnectBridge returns different shapes
    $customerName = $result['name']          ?? $result['customer_name']    ?? $result['data']['name']          ?? null;
    $address      = $result['address']       ?? $result['customer_address'] ?? $result['data']['address']       ?? null;
    $package      = $result['package']       ?? $result['current_package']  ?? $result['data']['package']       ?? null;
    $balance      = $result['balance']       ?? $result['outstanding']      ?? $result['data']['balance']       ?? null;
    $meterName    = $result['meter_name']    ?? $result['user']             ?? $result['data']['meter_name']    ?? null;
    $dueDate      = $result['due_date']      ?? $result['expiry_date']      ?? $result['data']['due_date']      ?? null;

    // Determine success: provider says status/success
    $provStatus = strtolower($result['status'] ?? $result['Status'] ?? '');
    $isSuccess  = in_array($provStatus, ['success', 'successful', 'ok', '00']) 
                  || isset($result['name']) || isset($result['customer_name'])
                  || isset($result['meter_name']) || isset($result['data']['name']);

    if (!$isSuccess) {
        $errMsg = $result['message'] ?? $result['detail'] ?? $result['error'] ?? 'Verification failed. Please check the number and try again.';
        sendJson(['status' => 'failed', 'message' => $errMsg]);
    }

    sendJson([
        'status' => 'success',
        'data'   => array_filter([
            'name'     => $customerName ?? $meterName ?? 'Customer',
            'address'  => $address,
            'package'  => $package,
            'balance'  => $balance,
            'due_date' => $dueDate,
        ]),
    ]);

} catch (Exception $e) {
    error_log('[Verify] ' . $e->getMessage());
    sendJson(['status' => 'failed', 'message' => 'Could not reach verification service. Try again.'], 503);
}
