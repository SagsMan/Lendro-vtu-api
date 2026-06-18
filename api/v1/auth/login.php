<?php
  /**
   * POST /api/v1/auth/login
   *
   * Authenticate by phone + PIN. Returns nested data the frontend caches.
   * Accepts application/x-www-form-urlencoded (default) or JSON body.
   */
  require_once __DIR__ . '/../db.php';

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
  }

  $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($contentType, 'application/json') !== false) {
      $body     = json_decode(file_get_contents('php://input'), true) ?? [];
      $phone    = trim($body['phone']    ?? '');
      $password = $body['pin'] ?? $body['password'] ?? '';
  } else {
      $phone    = trim($_POST['phone']    ?? '');
      $password = $_POST['pin'] ?? $_POST['password'] ?? '';
  }

  $phone = toPhone10($phone);

  if (empty($phone) || empty($password)) {
      sendJson(['status' => 'LE01', 'message' => 'Phone number and PIN are required.'], 422);
  }

  $stmt = $db->prepare(
      'SELECT u.id, u.name, u.email, u.phone, u.password,
              COALESCE(w.balance, 0)      AS wallet_balance,
              COALESCE(w.bucbalance, 0)   AS bucbalance,
              COALESCE(w.loanlimit, 0)    AS loanlimit,
              COALESCE(w.loancount, 0)    AS loancount,
              COALESCE(w.totalscore, 0)   AS totalscore,
              COALESCE(w.upoint, 0)       AS upoint,
              COALESCE(w.usage_recent, 0) AS usage_recent,
              COALESCE(w.vscore, 0)       AS vscore,
              COALESCE(w.repayscore, 0)   AS repayscore,
              COALESCE(w.ctpoint, 0)      AS ctpoint,
              w.plan,
              (SELECT MAX(w2.usage_recent) FROM wallets w2) AS maxusage
         FROM users u
         LEFT JOIN wallets w ON w.userid = u.id
        WHERE u.phone = ?
        LIMIT 1'
  );
  $stmt->execute([$phone]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user || !password_verify($password, $user['password'])) {
      sendJson(['status' => 'failed', 'message' => 'Incorrect phone number or PIN.'], 401);
  }

  $_SESSION['globaluid'] = $user['id'];
  $_SESSION['username']  = $user['name'];

  $maxusage = ($user['maxusage'] > 100) ? $user['maxusage'] : 200;

  $wallet = [
      'balance'    => (float) $user['wallet_balance'],
      'bucbalance' => (float) $user['bucbalance'],
      'loanlimit'  => (float) $user['loanlimit'],
      'loancount'  => (int)   $user['loancount'],
      'totalscore' => (int)   $user['totalscore'],
      'plan'       => $user['plan'],
      'maxusage'   => $maxusage,
      'scores'     => [
          'U'    => (int) $user['usage_recent'],
          'UALL' => (int) $user['upoint'],
          'V'    => (int) $user['vscore'],
          'R'    => (int) $user['repayscore'],
          'C'    => (int) $user['ctpoint'],
      ],
  ];

  // Services: try cache first
  $services = null;
  $cached = getCache($db, 'services_all');
  if ($cached) {
      $services = $cached;
  } else {
      $svc = json_decode(getAllServices($db), true);
      if (!empty($svc['data'])) {
          $services = $svc['data'];
          setCache($db, 'services_all', 'services', $services);
      }
  }

  sendJson([
      'status'  => 'success',
      'message' => "Welcome back, {$user['name']}!",
      'data'    => [
          'user' => [
              'id'    => $user['id'],
              'name'  => $user['name'],
              'email' => $user['email'],
              'phone' => $user['phone'],
          ],
          'wallet'   => $wallet,
          'alerts'   => [],
          'services' => $services,
      ],
  ]);
  