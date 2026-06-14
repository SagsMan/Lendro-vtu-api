<?php
/**
 * GET /api/v1/client/status?ref=LDR-xxx
 *
 * Poll the status of a transaction by its reference number.
 * Safe to call repeatedly — this is a pure read-only endpoint.
 *
 * The frontend should poll this every 5 seconds after placing an order
 * until status is "success", "failed", or "reversed".
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../TransactionService.php';

$userid    = requireAuth();
$reference = trim($_GET['ref'] ?? '');

if (empty($reference)) {
    sendJson(['status' => 'failed', 'message' => 'ref (transaction reference) is required.'], 422);
}

$result = TransactionService::getStatus($reference, $userid, $db);

sendJson($result);
