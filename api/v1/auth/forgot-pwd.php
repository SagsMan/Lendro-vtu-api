<?php
  /**
   * POST /api/v1/auth/forgot-pwd
   * Verify phone exists, then skip OTP — client proceeds to set-new-PIN screen.
   * Fields: fstr (phone or email)
   */
  require_once __DIR__ . '/../db.php';

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
  }

  $fstr = trim($_POST['fstr'] ?? $_POST['phone'] ?? $_POST['email'] ?? '');

  if (empty($fstr)) {
      sendJson(['status' => 'failed', 'message' => 'Please enter your phone number or email.'], 422);
  }

  $lookup = $fstr;
  if (preg_match('/^\d/', $fstr)) {
      $lookup = toPhone10($fstr);
  }

  $stmt = $db->prepare('SELECT id FROM users WHERE phone = ? OR email = ? LIMIT 1');
  $stmt->execute([$lookup, strtolower($fstr)]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
      sendJson(['status' => 'success', 'message' => 'If that account exists, you can now reset your PIN.']);
  }

  sendJson(['status' => 'success', 'message' => 'Account verified. Please set your new PIN.']);
  