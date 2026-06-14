<?php
/**
 * POST /api/v1/auth/register
 *
 * Register a new user account.
 *
 * Request body (JSON):
 *   name     string  full name
 *   email    string  valid email address
 *   phone    string  Nigerian phone number
 *   password string  min 8 characters
 */
require_once __DIR__ . '/../db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$name     = trim($body['name']     ?? '');
$email    = strtolower(trim($body['email']    ?? ''));
$phone    = trim($body['phone']    ?? '');
$password = $body['password'] ?? '';

// ── Validation ────────────────────────────────────────────────────────────────
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}
if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}
if (empty($phone)) {
    $errors[] = 'Phone number is required.';
}

if (!empty($errors)) {
    sendJson(['status' => 'failed', 'errors' => $errors], 422);
}

// ── Duplicate check ───────────────────────────────────────────────────────────
$stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    sendJson(['status' => 'failed', 'message' => 'An account with this email already exists.'], 409);
}

// ── Create account ────────────────────────────────────────────────────────────
try {
    $db->beginTransaction();

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $db->prepare(
        'INSERT INTO users (name, email, phone, password, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$name, $email, $phone, $hash]);

    $userId = (int) $db->lastInsertId();

    // Every user gets a wallet starting at zero balance
    $stmt = $db->prepare(
        'INSERT INTO wallets (userid, balance, created_at) VALUES (?, 0.00, NOW())'
    );
    $stmt->execute([$userId]);

    $db->commit();

    sendJson([
        'status'  => 'success',
        'message' => 'Account created successfully. Please log in.',
        'user_id' => $userId,
    ], 201);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[register] ' . $e->getMessage());
    sendJson(['status' => 'failed', 'message' => 'Registration failed. Please try again.'], 500);
}
