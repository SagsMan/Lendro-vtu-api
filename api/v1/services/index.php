<?php
/**
 * GET /api/v1/services/index.php
 *
 * Returns all active services in the format the frontend expects:
 *   { status: "success", data: { services: { airtime: [...], data: [...], categories: [...] } } }
 *
 * Called by app.html refreshData() when page === "services".
 */
require_once __DIR__ . '/../db.php';
requireAuth();

$ver = isset($_POST['ver']) ? trim($_POST['ver']) : null;

// Return cached version if client already has today's data
if ($ver && strtotime($ver) >= strtotime('today')) {
    toJSON(['status' => 'success', 'data' => ['services' => null]]);
    exit;
}

$svc = json_decode(getAllServices($db), true);
toJSON(['status' => 'success', 'data' => ['services' => $svc['data'] ?? []]]);
