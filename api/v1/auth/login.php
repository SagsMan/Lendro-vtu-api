<?php
/**
 * POST /api/v1/auth/login
 *
 * Authenticate a user and start a session.
 *
 * Request body (JSON):
 *   email    string
 *   password string
 */
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = strtolower(trim($body['email']    ?? ''));
$password = $body['password'] ?? '';

if (empty($email) || empty($password)) {
    sendJson(['status' => 'failed', 'message' => 'Email and password are required.'], 422);
}

// Fetch user + wallet balance in one query
$stmt = $db->prepare(
    'SELECT u.id, u.name, u.email, u.phone, u.password,
            COALESCE(w.balance, 0) AS wallet_balance
       FROM users u
       LEFT JOIN wallets w ON w.userid = u.id
      WHERE u.email = ?
      LIMIT 1'
);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    sendJson(['status' => 'failed', 'message' => 'Incorrect email or password.'], 401);
}

// Start authenticated session
$_SESSION['globaluid'] = $user['id'];
$_SESSION['username']  = $user['name'];

sendJson([
    'status'  => 'success',
    'message' => "Welcome back, {$user['name']}!",
    'user'    => [
        'id'             => $user['id'],
        'name'           => $user['name'],
        'email'          => $user['email'],
        'phone'          => $user['phone'],
        'wallet_balance' => (float) $user['wallet_balance'],
    ],
]);
