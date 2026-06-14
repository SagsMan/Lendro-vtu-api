<?php
/**
 * GET /api/v1/client/transactions
 *
 * List the authenticated user's transaction history.
 *
 * Query params:
 *   ?page=1        page number (default: 1)
 *   ?limit=20      items per page (max: 50)
 *   ?status=       filter by status (pending|processing|success|failed|reversed)
 */
require_once __DIR__ . '/../db.php';

$userid = requireAuth();

$page   = max(1, (int) ($_GET['page']  ?? 1));
$limit  = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;
$status = trim($_GET['status'] ?? '');

$sql    = 'SELECT t.*, s.name AS service_name, p.name AS provider_name
             FROM transactions t
             LEFT JOIN services  s ON s.id = t.service_id
             LEFT JOIN providers p ON p.id = t.provider_id
            WHERE t.userid = ?';
$params = [$userid];

if (!empty($status)) {
    $sql    .= ' AND t.status = ?';
    $params[] = $status;
}

$sql .= ' ORDER BY t.created_at DESC LIMIT ? OFFSET ?';
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total count for pagination
$countSql    = 'SELECT COUNT(*) FROM transactions WHERE userid = ?';
$countParams = [$userid];
if (!empty($status)) {
    $countSql    .= ' AND status = ?';
    $countParams[] = $status;
}
$countStmt = $db->prepare($countSql);
$countStmt->execute($countParams);
$total = (int) $countStmt->fetchColumn();

$transactions = array_map(function ($tx) {
    return [
        'reference'    => $tx['refno'],
        'service'      => $tx['service_name'] ?? $tx['transtitle'],
        'provider'     => $tx['provider_name'],
        'amount'       => (float) $tx['amount'],
        'phone'        => $tx['phone'],
        'status'       => $tx['status'],
        'created_at'   => $tx['created_at'],
        'updated_at'   => $tx['updated_at'],
        'time_ago'     => timeAgo($tx['created_at']),
    ];
}, $rows);

sendJson([
    'status'       => 'success',
    'page'         => $page,
    'limit'        => $limit,
    'total'        => $total,
    'total_pages'  => (int) ceil($total / $limit),
    'transactions' => $transactions,
]);
