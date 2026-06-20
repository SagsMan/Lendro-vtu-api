<?php
/**
 * POST /api/v1/accounts/deposit
 *
 * Two-step Squad wallet deposit flow:
 *
 * Step 1  category=deposit  status=pending
 *   Creates a pending transaction + returns the reference for the Squad widget.
 *
 * Step 2  category=deposited  status=processed
 *   Called after the Squad widget completes.
 *   Verifies payment with Squad, credits wallet, marks transaction success.
 */
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
}

$userid   = requireAuth();
$category = trim($_POST['category'] ?? '');
$status   = trim($_POST['status']   ?? '');

// ─────────────────────────────────────────────────────────────────────────────
// STEP 1 — Initiate: record pending transaction + return ref to frontend
// ─────────────────────────────────────────────────────────────────────────────
if ($category === 'deposit' && $status === 'pending') {

    $amount = (float) ($_POST['amount'] ?? 0);
    $fee    = (float) ($_POST['fee']    ?? ($DWFee * $amount));
    $total  = (float) ($_POST['total']  ?? ($amount + $fee));

    if ($amount < 100) {
        sendJson(['status' => 'failed', 'message' => 'Minimum deposit amount is ₦100.'], 422);
    }

    // Fetch email for Squad widget
    $stmt = $db->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userid]);
    $user  = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = $user['email'] ?? 'user@lendro.ng';

    $refno = generateRefNo('LDR-DEP');

    $stmt = $db->prepare(
        "INSERT INTO transactions
            (userid, service_id, amount, transtype, refno, transtitle, transdesc, status, created_at)
         VALUES (?, NULL, ?, 'deposit', ?, 'Wallet Deposit', ?, 'pending', NOW())"
    );
    $stmt->execute([$userid, $amount, $refno, "Deposit ₦{$amount} (fee ₦{$fee})"]);

    sendJson([
        'status' => 'success',
        'data'   => [
            'amount'          => $total,
            'email'           => $email,
            'transaction_ref' => $refno,
        ],
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// STEP 2 — Verify: Squad confirmed payment; credit wallet
// ─────────────────────────────────────────────────────────────────────────────
if ($category === 'deposited' && $status === 'processed') {

    $refno = trim($_POST['refno'] ?? '');
    $total = (float) ($_POST['total'] ?? 0);

    if (!$refno) {
        sendJson(['status' => 'failed', 'message' => 'Transaction reference is required.'], 422);
    }

    // ── Verify with Squad ─────────────────────────────────────────────────────
    $verifyUrl = rtrim($squard_Endpoint, '/') . '/transaction/verify/' . urlencode($refno);

    $ch = curl_init($verifyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET        => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $squad_SecretKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw       = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        error_log("[deposit verify] cURL error: {$curlError}");
        sendJson(['status' => 'failed', 'message' => 'Payment gateway timeout. Contact support if your account was debited.'], 502);
    }

    $res = json_decode($raw, true);

    // Squad returns: { "status": 200, "success": true, "data": { "transaction_status": "success", "transaction_amount": 50000 } }
    // transaction_amount is in kobo (smallest unit)
    $squadStatus  = (int) ($res['status'] ?? $httpCode ?? 0);
    $txStatus     = strtolower(trim((string) ($res['data']['transaction_status'] ?? '')));
    $txAmountKobo = (float) ($res['data']['transaction_amount'] ?? 0);
    $txAmount     = $txAmountKobo / 100; // kobo → naira

    // Accept "success" or "successful" from Squad
    $isSuccess = in_array($txStatus, ['success', 'successful', 'completed']);

    if ($squadStatus !== 200 || !$isSuccess) {
        error_log("[deposit verify] Squad response: HTTP {$httpCode} | status={$txStatus} | raw=" . substr($raw, 0, 500));
        sendJson([
            'status'  => 'failed',
            'message' => 'Payment was not confirmed by Squad. Please try again or contact support.',
            'data'    => [],
        ]);
    }

    // ── Credit wallet atomically ──────────────────────────────────────────────
    try {
        $db->beginTransaction();

        // Lock transaction row — prevent double-processing
        $stmt = $db->prepare(
            'SELECT id, amount, status FROM transactions
              WHERE userid = ? AND refno = ?
              LIMIT 1 FOR UPDATE'
        );
        $stmt->execute([$userid, $refno]);
        $txRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$txRow) {
            throw new Exception('Transaction record not found.');
        }
        if ($txRow['status'] !== 'pending') {
            $db->commit();
            sendJson(['status' => 'failed', 'message' => 'This deposit has already been processed.']);
        }

        // Determine amount to credit
        $declaredAmount = (float) $txRow['amount'];

        if ($txAmount > 0 && $txAmount < $declaredAmount) {
            // Squad deducted a gateway fee
            $nFee    = $declaredAmount - $txAmount;
            $nAmount = $txAmount;
        } else {
            // Full amount or Squad sent more (shouldn't happen, but handle it)
            $nFee    = $declaredAmount * $DWFee;
            $nAmount = $declaredAmount - $nFee;
        }

        // Lock wallet
        $stmt = $db->prepare('SELECT * FROM wallets WHERE userid = ? FOR UPDATE');
        $stmt->execute([$userid]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            throw new Exception('Wallet not found. Please contact support.');
        }

        $balanceBefore = (float) $wallet['balance'];
        $balanceAfter  = $balanceBefore + $nAmount;

        // Credit wallet
        $stmt = $db->prepare('UPDATE wallets SET balance = ?, updated_at = NOW() WHERE userid = ?');
        $stmt->execute([$balanceAfter, $userid]);

        logWalletEvent($db, $userid, 'credit', $nAmount, $balanceBefore, $balanceAfter, $refno, "Deposit ₦" . number_format($nAmount, 2));

        // Mark transaction success
        $desc = "Deposit ₦" . number_format($nAmount, 2) . " (fee ₦" . number_format($nFee, 2) . ")";
        $stmt = $db->prepare(
            "UPDATE transactions
                SET status = 'success', transdesc = ?, amount = ?, updated_at = NOW()
              WHERE id = ?"
        );
        $stmt->execute([$desc, $nAmount, $txRow['id']]);

        $db->commit();

        // Return updated wallet
        $stmt = $db->prepare('SELECT balance FROM wallets WHERE userid = ? LIMIT 1');
        $stmt->execute([$userid]);
        $updatedWallet = $stmt->fetch(PDO::FETCH_ASSOC);

        sendJson([
            'status'  => 'success',
            'message' => '₦' . number_format($nAmount, 2) . ' deposited to your wallet.',
            'data'    => [
                'balance'     => (float) $updatedWallet['balance'],
                'amount'      => $nAmount,
                'fee'         => $nFee,
                'reference'   => $refno,
            ],
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[deposit] ' . $e->getMessage());
        sendJson(['status' => 'failed', 'message' => $e->getMessage(), 'data' => []]);
    }
}

// Unknown action
sendJson(['status' => 'failed', 'message' => 'Invalid deposit request.'], 422);
