<?php
/**
 * POST /api/v1/webhooks/provider?provider=cheapdatahub
 *
 * Webhook receiver for real-time provider callbacks.
 *
 * When a provider finishes processing a transaction on their end, they POST
 * the result here. This is faster than waiting for the reconciliation worker
 * to poll — the user gets notified instantly.
 *
 * Usage:
 *   Give providers this URL:
 *     https://yourdomain.com/api/v1/webhooks/provider.php?provider=cheapdatahub
 *     https://yourdomain.com/api/v1/webhooks/provider.php?provider=connectbridge
 *
 * Security:
 *   Uncomment the signature verification block below and fill in the correct
 *   HMAC secret once you know each provider's signing scheme.
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../Normalizer.php';
require_once __DIR__ . '/../helpers/QueueHelper.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'failed', 'message' => 'Method not allowed']);
    exit;
}

// ── Read and decode the raw payload ──────────────────────────────────────────
$rawPayload = file_get_contents('php://input');

if (empty($rawPayload)) {
    http_response_code(400);
    echo json_encode(['status' => 'failed', 'message' => 'Empty payload']);
    exit;
}

$data = json_decode($rawPayload, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'failed', 'message' => 'Invalid JSON']);
    exit;
}

// ── Identify the provider ─────────────────────────────────────────────────────
// Accept it from the query string (preferred) or from the payload body
$providerSlug = strtolower(trim(
    $_GET['provider'] ?? $data['provider'] ?? ''
));

if (empty($providerSlug)) {
    $providerSlug = 'cheapdatahub'; // default fallback
}

// Verify the provider exists in our database
$stmt = $db->prepare('SELECT * FROM providers WHERE slug = ? LIMIT 1');
$stmt->execute([$providerSlug]);
$providerRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$providerRow) {
    http_response_code(404);
    echo json_encode(['status' => 'failed', 'message' => "Unknown provider: {$providerSlug}"]);
    exit;
}

// ── Optional: Verify HMAC signature ──────────────────────────────────────────
// Uncomment and adapt to your provider's signing spec
/*
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$expected  = hash_hmac('sha256', $rawPayload, $providerRow['webhook_secret']);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    echo json_encode(['status' => 'failed', 'message' => 'Invalid signature']);
    exit;
}
*/

// ── Normalise the provider's callback into our internal format ────────────────
$normalised       = Normalizer::normalizeProviderWebhook($providerSlug, $data);
$reference        = trim($normalised['reference'] ?? '');
$status           = strtolower(trim($normalised['status'] ?? ''));
$providerRef      = trim($normalised['provider_reference'] ?? '');

if (empty($reference)) {
    http_response_code(400);
    echo json_encode(['status' => 'failed', 'message' => 'Missing transaction reference in payload']);
    exit;
}

// ── Log the raw callback for auditing ────────────────────────────────────────
$stmt = $db->prepare(
    'INSERT INTO provider_callbacks (provider_id, reference, payload, status, created_at)
     VALUES (?, ?, ?, ?, NOW())'
);
$stmt->execute([$providerRow['id'], $reference, $rawPayload, $status]);

// ── Process the callback ──────────────────────────────────────────────────────
try {
    $db->beginTransaction();

    // Lock the transaction to prevent race with the reconciliation worker
    $stmt = $db->prepare('SELECT * FROM transactions WHERE refno = ? LIMIT 1 FOR UPDATE');
    $stmt->execute([$reference]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'failed', 'message' => "Transaction not found: {$reference}"]);
        exit;
    }

    // ── Already resolved — acknowledge without changing anything ──────────
    if (in_array($transaction['status'], ['success', 'reversed', 'failed'])) {
        $db->commit();
        echo json_encode(['status' => 'already_processed', 'current_status' => $transaction['status']]);
        exit;
    }

    // ── SUCCESS callback ──────────────────────────────────────────────────
    if ($status === 'success') {

        $stmt = $db->prepare(
            "UPDATE transactions
                SET status            = 'success',
                    provider_id       = ?,
                    provider_reference = ?,
                    provider_status   = ?,
                    callback_data     = ?,
                    reconciled        = 1,
                    completed_at      = NOW(),
                    updated_at        = NOW()
              WHERE refno = ?"
        );
        $stmt->execute([$providerRow['id'], $providerRef, $status, $rawPayload, $reference]);

        updateQueueStatus($db, $reference, 'completed');

        $stmt = $db->prepare("INSERT INTO notifications (userid, message, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$transaction['userid'], "Your {$transaction['transtitle']} transaction was successful!"]);

        $db->commit();
        echo json_encode(['status' => 'success']);
        exit;
    }

    // ── FAILED callback ───────────────────────────────────────────────────
    if ($status === 'failed') {

        // Lock wallet for refund
        $stmt = $db->prepare('SELECT * FROM wallets WHERE userid = ? FOR UPDATE');
        $stmt->execute([$transaction['userid']]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($wallet) {
            $before = (float) $wallet['balance'];
            $after  = $before + (float) $transaction['amount'];

            $stmt = $db->prepare('UPDATE wallets SET balance = ?, updated_at = NOW() WHERE userid = ?');
            $stmt->execute([$after, $transaction['userid']]);

            $stmt = $db->prepare(
                'INSERT INTO wallet_logs (userid, type, amount, balance_before, balance_after, reference, description, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$transaction['userid'], 'credit', $transaction['amount'], $before, $after, $reference, 'Refund: provider callback failed']);
        }

        $stmt = $db->prepare(
            "UPDATE transactions
                SET status             = 'reversed',
                    provider_id        = ?,
                    provider_reference = ?,
                    provider_status    = ?,
                    callback_data      = ?,
                    reconciled         = 1,
                    updated_at         = NOW()
              WHERE refno = ?"
        );
        $stmt->execute([$providerRow['id'], $providerRef, $status, $rawPayload, $reference]);

        updateQueueStatus($db, $reference, 'failed');

        $stmt = $db->prepare("INSERT INTO notifications (userid, message, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$transaction['userid'], "Transaction {$reference} failed. Your wallet has been refunded."]);

        $db->commit();
        echo json_encode(['status' => 'refunded']);
        exit;
    }

    // ── PROCESSING / PENDING callback ─────────────────────────────────────
    // Provider is still working on it — flag for reconciliation follow-up
    $stmt = $db->prepare(
        "UPDATE transactions
            SET status             = 'processing',
                provider_id        = ?,
                provider_reference = ?,
                provider_status    = ?,
                callback_data      = ?,
                updated_at         = NOW()
          WHERE refno = ?"
    );
    $stmt->execute([$providerRow['id'], $providerRef, $status, $rawPayload, $reference]);

    updateQueueStatus($db, $reference, 'awaiting_reconciliation');

    $db->commit();
    echo json_encode(['status' => 'processing', 'message' => 'Queued for reconciliation']);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[Webhook] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'failed', 'message' => 'Internal server error']);
}
