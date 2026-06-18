<?php
  /**
   * POST /api/v1/auth/reset-pin
   *
   * Reset a user's PIN without OTP verification (OTP skipped for now).
   *
   * Fields: phone (10-digit), pin (new 6-digit PIN)
   */
  require_once __DIR__ . '/../db.php';

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
  }

  $phone = trim($_POST['phone'] ?? '');
  $pin   = $_POST['pin'] ?? '';

  $phone = toPhone10($phone);

  if (empty($phone) || strlen($phone) < 10) {
      sendJson(['status' => 'failed', 'message' => 'A valid phone number is required.'], 422);
  }
  if (strlen($pin) < 6) {
      sendJson(['status' => 'failed', 'message' => 'PIN must be 6 digits.'], 422);
  }

  // Check user exists
  $stmt = $db->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
  $stmt->execute([$phone]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
      sendJson(['status' => 'failed', 'message' => 'No account found with that phone number.'], 404);
  }

  // Update password
  $hash = password_hash($pin, PASSWORD_BCRYPT);
  $stmt = $db->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE phone = ?');
  $stmt->execute([$hash, $phone]);

  sendJson(['status' => 'success', 'message' => 'PIN updated successfully. Please log in.']);
  