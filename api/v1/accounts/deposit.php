<?php
/**
 * POST /api/v1/accounts/deposit
 *
 * Two-step SquadCo payment deposit flow:
 *
 * Step 1 — Initiate (category=deposit, status=pending)
 *   Creates a pending transaction + returns the reference for the SquadCo widget.
 *
 * Step 2 — Verify (category=deposited, status=processed)
 *   Called after SquadCo payment widget completes.
 *   Verifies the payment with SquadCo, credits the wallet, marks the transaction.
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
}

$userid   = requireAuth();
$category = trim($_POST['category'] ?? '');
$status   = trim($_POST['status']   ?? '');

// ────────────────────────────────────────────────────────────────────────────────
// STEP 1: Initiate deposit — record a pending transaction + return ref to frontend
// ────────────────────────────────────────────────────────────────────────────────
if ($category === 'deposit' && $status === 'pending') {

    $amount = (float) ($_POST['amount'] ?? 0);
    $fee    = (float) ($_POST['fee']    ?? ($DWFee * $amount));
    $total  = (float) ($_POST['total']  ?? ($amount + $fee));

    if ($amount < 100) {
        sendJson(['status' => 'failed', 'message' => 'Minimum deposit amount is ₦100.'], 422);
    }

    // Fetch the user's email (SquadCo widget needs it)
    $stmt = $db->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userid]);
    $user  = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = $user['email'] ?? 'user@lendro.ng';

    // Generate a unique reference for this deposit
    $refno = generateRefNo('LDR-DEP');

    // Create a pending transaction — we'll update it to "success" after verification
    $stmt = $db->prepare(
        "INSERT INTO transactions
            (userid, service_id, amount, transtype, refno, transtitle, transdesc, status, created_at)
         VALUES (?, NULL, ?, 'deposit', ?, 'Deposit', ?, 'pending', NOW())"
    );
    $stmt->execute([
        $userid,
        $amount,
        $refno,
        "Deposit ₦{$amount} (fee ₦{$fee})",
    ]);

    sendJson([
        'status' => 'success',
        'data'   => [
            'amount'          => $total,
            'email'           => $email,
            'transaction_ref' => $refno,
        ],
    ]);
}

// ────────────────────────────────────────────────────────────────────────────────
// STEP 2: Verify — SquadCo has processed payment; confirm + credit wallet
// ────────────────────────────────────────────────────────────────────────────────
if ($category === 'deposited' && $status === 'processed') {

    $refno = trim($_POST['refno'] ?? '');
    $total = (float) ($_POST['total'] ?? 0);

    if (!$userid) {
        sendJson(['status' => 'failed', 'message' => 'Invalid user account.'], 401);
    }
    if (!$refno) {
        sendJson(['status' => 'failed', 'message' => 'Transaction reference is required.'], 422);
    }

    // ── Verify with SquadCo ──────────────────────────────────────────────────
    // Use the configured SquadCo secret key from configs.php ($squad_SecretKey)
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
    curl_close($ch);

    if ($curlError) {
        error_log("[deposit verify] cURL error: {$curlError}");
        sendJson(['status' => 'failed', 'message' => 'Payment gateway timeout. Contact support if your account was debited.'], 502);
    }

    $res = json_decode($raw, true);

    // SquadCo returns { "status": 200, "data": { "transaction_status": "success", "transaction_amount": 50000 }}
    $squadStatus = (int) ($res['status'] ?? 0);
    $txStatus    = strtolower((string) ($res['data']['transaction_status'] ?? ''));
    $txAmountKobo = (float) ($res['data']['transaction_amount'] ?? 0);
    $txAmount     = $txAmountKobo / 100; // convert kobo → naira

    if ($squadStatus !== 200 || $txStatus !== 'success') {
        sendJson(['status' => 'failed', 'message' => 'Payment was not confirmed by SquadCo. Please try again.', 'data' => []]);
    }

    // ── Credit wallet atomically ─────────────────────────────────────────────
    try {
        $db->beginTransaction();

        // Lock our transaction record — prevent double-processing
        $stmt = $db->prepare(
            'SELECT id, amount, status, created_at FROM transactions
              WHERE userid = ? AND refno = ?
              LIMIT 1 FOR UPDATE'
        );
        $stmt->execute([$userid, $refno]);
        $txRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$txRow) {
            throw new Exception('Transaction record not found.');
        }
        if ($txRow['status'] !== 'pending') {
            throw new Exception('This deposit has already been processed.');
        }

        // Calculate how much to actually credit (handle SquadCo fee discrepancy)
        $declaredAmount = (float) $txRow['amount'];

        if ($txAmount >= $declaredAmount) {
            // SquadCo sent more than or exactly what was expected
            $nFee    = $txAmount - $declaredAmount;
            $nAmount = $declaredAmount;
        } else {
            // SquadCo deducted a transaction fee from the total paid
            $nFee    = $txAmount * $DWFee;
            $nAmount = $txAmount - $nFee;
        }

        // Lock wallet
        $stmt = $db->prepare('SELECT * FROM wallets WHERE userid = ? FOR UPDATE');
        $stmt->execute([$userid]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            throw new Exception('Wallet not found. Contact support.');
        }

        $balanceBefore = (float) $wallet['balance'];
        $balanceAfter  = $balanceBefore + $nAmount;

        // Credit wallet
        $stmt = $db->prepare('UPDATE wallets SET balance = ?, updated_at = NOW() WHERE userid = ?');
        $stmt->execute([$balanceAfter, $userid]);

        logWalletEvent($db, $userid, 'credit', $nAmount, $balanceBefore, $balanceAfter, $refno, "Deposit ₦{$nAmount}");

        // Update transaction status
        $desc = "Deposit ₦{$nAmount} (fee ₦{$nFee})";
        $stmt = $db->prepare(
            "UPDATE transactions
                SET status = 'success', transdesc = ?, amount = ?, updated_at = NOW()
              WHERE id = ?"
        );
        $stmt->execute([$desc, $nAmount, $txRow['id']]);

        $db->commit();

        // Return updated wallet + the new transaction entry
        $stmt = $db->prepare('SELECT * FROM wallets WHERE userid = ? LIMIT 1');
        $stmt->execute([$userid]);
        $updatedWallet = $stmt->fetch(PDO::FETCH_ASSOC);

        $txEntry = [
            'id'          => $txRow['id'],
            'type'        => 'deposit',
            'description' => 'Deposit',
            'amount'      => $nAmount,
            'time'        => 'Just now',
            'status'      => 'success',
        ];

        sendJson([
            'status'  => 'success',
            'message' => "₦" . number_format($nAmount, 2) . " deposit was successful.",
            'data'    => [
                'wallet'       => $updatedWallet,
                'amount'       => $nAmount,
                'fee'          => $nFee,
                'transactions' => $txEntry,
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

// ── Unknown action ────────────────────────────────────────────────────────────
sendJson(['status' => 'failed', 'message' => 'Invalid deposit request.'], 422);
