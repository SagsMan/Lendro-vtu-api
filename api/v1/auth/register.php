<?php
  /**
   * POST /api/v1/auth/register
   *
   * Register a new user account.
   * Accepts application/x-www-form-urlencoded (frontend) or JSON body.
   *
   * Fields: fullname (or name), email, phone, pin (or password), confirmPin
   */
  require_once __DIR__ . '/../db.php';

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
  }

  // Support both URL-encoded POST and JSON body
  $body = [];
  $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($contentType, 'application/json') !== false) {
      $body = json_decode(file_get_contents('php://input'), true) ?? [];
  } else {
      $body = $_POST;
  }

  $name     = trim($body['fullname'] ?? $body['name'] ?? '');
  $email    = strtolower(trim($body['email'] ?? ''));
  $phone    = trim($body['phone'] ?? '');
  $password = $body['pin'] ?? $body['password'] ?? '';

  // Normalise phone to 10 digits
  $phone = toPhone10($phone);

  // ── Validation ──────────────────────────────────────────────
  $errors = [];

  if (empty($name)) {
      sendJson(['status' => 'RE01', 'message' => 'Full name is required.'], 422);
  }
  if (empty($phone) || strlen($phone) < 10) {
      sendJson(['status' => 'RE01', 'message' => 'A valid 10-digit phone number is required.'], 422);
  }
  if (strlen($password) < 6) {
      sendJson(['status' => 'RE02', 'message' => 'PIN must be at least 6 digits.'], 422);
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      sendJson(['status' => 'RE03', 'message' => 'A valid email address is required.'], 422);
  }

  // ── Duplicate checks ────────────────────────────────────────
  $stmt = $db->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
  $stmt->execute([$phone]);
  if ($stmt->fetch()) {
      sendJson(['status' => 'RE01', 'message' => 'An account with this phone number already exists.'], 409);
  }

  $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  if ($stmt->fetch()) {
      sendJson(['status' => 'RE03', 'message' => 'An account with this email already exists.'], 409);
  }

  // ── Create account ──────────────────────────────────────────
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
  