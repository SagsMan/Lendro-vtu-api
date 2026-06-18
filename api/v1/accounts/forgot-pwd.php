<?php
  /**
   * POST /api/v1/accounts/forgot-pwd
   *
   * Send a password reset link/OTP. OTP delivery is disabled for now;
   * the endpoint always returns success so the UI can progress.
   *
   * Fields: fstr (email or phone)
   */
  require_once __DIR__ . '/../db.php';

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
  }

  $fstr = trim($_POST['fstr'] ?? $_POST['phone'] ?? $_POST['email'] ?? '');

  if (empty($fstr)) {
      sendJson(['status' => 'failed', 'message' => 'Please enter your phone number or email.'], 422);
  }

  // Look up the user by phone or email
  $stmt = $db->prepare('SELECT id, name, email, phone FROM users WHERE email = ? OR phone = ? LIMIT 1');
  $stmt->execute([$fstr, $fstr]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  // Always return success to avoid user enumeration, even if not found
  // TODO: integrate SMS/email OTP gateway here when ready
  sendJson([
      'status'  => 'success',
      'message' => 'If that account exists, a reset OTP will be sent to your phone number and email.',
  ]);
  