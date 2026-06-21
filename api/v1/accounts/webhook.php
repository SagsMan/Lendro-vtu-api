<?php
/**
 * POST /api/v1/accounts/webhook.php
 *
 * Squad payment webhook — called by Squad when a payment is confirmed.
 * Verifies HMAC-SHA512 signature, then credits the wallet for "charge.success".
 *
 * Squad docs: https://squadinc.gitbook.io/squad-api-documentation/payments/webhooks
 * Signature header: x-squad-encrypted-body = hash_hmac('sha512', raw_body, secret_key)
 */
require_once __DIR__ . '/../db.php';

// ── Only accept POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// ── Read raw body ─────────────────────────────────────────────────────────────
$rawBody = file_get_contents('php://input');

// ── Verify Squad HMAC-SHA512 signature ───────────────────────────────────────
$squadSig = $_SERVER['HTTP_X_SQUAD_ENCRYPTED_BODY'] ?? '';
if ($squad_SecretKey && $squadSig) {
    $expected = hash_hmac('sha512', $rawBody, $squad_SecretKey);
    if (!hash_equals(strtolower($expected), strtolower($squadSig))) {
        error_log('[webhook] Invalid signature. Got: ' . substr($squadSig, 0, 16));
        http_response_code(401);
        exit('Invalid signature');
    }
}

// ── Parse payload ─────────────────────────────────────────────────────────────
$payload = json_decode($rawBody, true);
$event   = $payload['Event'] ?? '';
$body    = $payload['Body']  ?? [];

error_log('[webhook] Event: ' . $event . ' | ref: ' . ($body['transaction_ref'] ?? '-'));

// ── Only handle charge.success ────────────────────────────────────────────────
if ($event !== 'charge.success') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'event' => $event]);
    exit;
}

$refno        = trim((string) ($body['transaction_ref'] ?? ''));
$amountKobo   = (float) ($body['amount'] ?? 0);
$squadStatus  = strtolower(trim((string) ($body['transaction_status'] ?? '')));

if (!$refno) {
    error_log('[webhook] Missing transaction_ref');
    http_response_code(400);
    exit('Missing ref');
}

// Only credit on confirmed success from Squad
if (!in_array($squadStatus, ['success', 'successful', 'completed'])) {
    error_log('[webhook] Not a success status: ' . $squadStatus);
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'reason' => 'not_success', 'tx_status' => $squadStatus]);
    exit;
}

$txAmountNaira = $amountKobo / 100;

// ── Credit wallet atomically ──────────────────────────────────────────────────
try {
    $db->beginTransaction();

    // Find the transaction — match by refno, must be pending deposit
    $stmt = $db->prepare(
        "SELECT id, userid, amount, status FROM transactions
          WHERE refno = ? AND transtype = 'deposit'
          LIMIT 1 FOR UPDATE"
    );
    $stmt->execute([$refno]);
    $txRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$txRow) {
        $db->commit();
        error_log('[webhook] Transaction not found: ' . $refno);
        http_response_code(200);
        echo json_encode(['status' => 'not_found', 'ref' => $refno]);
        exit;
    }

    // Idempotency: skip if already processed
    if ($txRow['status'] !== 'pending') {
        $db->commit();
        error_log('[webhook] Already processed: ' . $refno . ' | status=' . $txRow['status']);
        http_response_code(200);
        echo json_encode(['status' => 'already_processed']);
        exit;
    }

    $userid         = (int) $txRow['userid'];
    $declaredAmount = (float) $txRow['amount'];

    // Determine credit amount
    if ($txAmountNaira > 0 && $txAmountNaira < $declaredAmount) {
        $nFee    = $declaredAmount - $txAmountNaira;
        $nAmount = $txAmountNaira;
    } else {
        $nFee    = $declaredAmount * $DWFee;
        $nAmount = $declaredAmount - $nFee;
    }

    // Lock wallet
    $stmt = $db->prepare('SELECT * FROM wallets WHERE userid = ? FOR UPDATE');
    $stmt->execute([$userid]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        throw new Exception('Wallet not found for user ' . $userid);
    }

    $balanceBefore = (float) $wallet['balance'];
    $balanceAfter  = $balanceBefore + $nAmount;

    $stmt = $db->prepare('UPDATE wallets SET balance = ?, updated_at = NOW() WHERE userid = ?');
    $stmt->execute([$balanceAfter, $userid]);

    logWalletEvent($db, $userid, 'credit', $nAmount, $balanceBefore, $balanceAfter, $refno,
        "Deposit ₦" . number_format($nAmount, 2) . " via Squad webhook");

    $desc = "Deposit ₦" . number_format($nAmount, 2) . " (fee ₦" . number_format($nFee, 2) . ")";
    $stmt = $db->prepare(
        "UPDATE transactions
            SET status = 'success', transdesc = ?, amount = ?, updated_at = NOW()
          WHERE id = ?"
    );
    $stmt->execute([$desc, $nAmount, $txRow['id']]);

    $db->commit();

    error_log('[webhook] Credited ₦' . $nAmount . ' to user ' . $userid . ' | ref=' . $refno);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'credited' => $nAmount, 'user' => $userid]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('[webhook] ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
