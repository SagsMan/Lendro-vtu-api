<?php
/**
 * GET /api/v1/client/wallet
 *
 * Return the authenticated user's current wallet balance and recent transactions.
 */
require_once __DIR__ . '/../db.php';

$userid = requireAuth();

// Wallet balance
$stmt = $db->prepare('SELECT balance FROM wallets WHERE userid = ? LIMIT 1');
$stmt->execute([$userid]);
$wallet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$wallet) {
    sendJson(['status' => 'failed', 'message' => 'Wallet not found.'], 404);
}

// Recent transactions (last 20)
$stmt = $db->prepare(
    'SELECT refno, amount, transtype, transtitle, status, created_at
       FROM transactions
      WHERE userid = ?
      ORDER BY created_at DESC
      LIMIT 20'
);
$stmt->execute([$userid]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

sendJson([
    'status'  => 'success',
    'balance' => (float) $wallet['balance'],
    'transactions' => array_map(function ($tx) {
        return [
            'reference' => $tx['refno'],
            'amount'    => (float) $tx['amount'],
            'type'      => $tx['transtype'],
            'service'   => $tx['transtitle'],
            'status'    => $tx['status'],
            'date'      => $tx['created_at'],
            'time_ago'  => timeAgo($tx['created_at']),
        ];
    }, $transactions),
]);
