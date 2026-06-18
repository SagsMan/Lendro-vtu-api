<?php
  require_once __DIR__ . '/../db.php';

  $uid = requireAuth();
  $clientVer = isset($_POST['ver']) ? trim($_POST['ver']) : null;
  $data = [];

  // Wallet + user info
  $stmt = $db->prepare(
      'SELECT u.id, u.name, u.phone, u.email,
              w.balance, w.plan, w.bucbalance, w.loanlimit, w.loancount,
              w.totalscore, w.usage_recent, w.upoint, w.vscore, w.repayscore, w.ctpoint,
              (SELECT MAX(w2.usage_recent) FROM wallets w2) AS maxusage
         FROM users u
         INNER JOIN wallets w ON u.id = w.userid
        WHERE u.id = ?
        LIMIT 1'
  );
  $stmt->execute([$uid]);
  $rs = $stmt->fetch(PDO::FETCH_ASSOC);
  $wallet = [];

  if ($rs) {
      $maxusage = ($rs['maxusage'] > 100) ? $rs['maxusage'] : 200;
      $wallet = [
          'balance'    => (float) ($rs['balance']    ?? 0),
          'bucbalance' => (float) ($rs['bucbalance'] ?? 0),
          'loanlimit'  => (float) ($rs['loanlimit']  ?? 0),
          'totalscore' => (int)   ($rs['totalscore'] ?? 0),
          'plan'       => $rs['plan'],
          'loancount'  => (int)   ($rs['loancount']  ?? 0),
          'scores'     => [
              'U'    => (int) $rs['usage_recent'],
              'UALL' => (int) $rs['upoint'],
              'V'    => (int) $rs['vscore'],
              'R'    => (int) $rs['repayscore'],
              'C'    => (int) $rs['ctpoint'],
          ],
          'maxusage' => $maxusage,
      ];
  }

  // Recent transactions
  $stmt = $db->prepare(
      'SELECT id, transtype, transtitle, amount, status, created_at
         FROM transactions
        WHERE userid = ?
        ORDER BY id DESC LIMIT 10'
  );
  $stmt->execute([$uid]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $transactions = [];

  foreach ($rows as $rs) {
      $timeago = timeAgo($rs['created_at']);
      if ($rs['transtype'] === 'deposit') {
          $timeago = ucfirst($rs['status']) . ' - ' . $timeago;
      }
      $transactions[] = [
          'id'          => $rs['id'],
          'type'        => $rs['transtype'],
          'description' => $rs['transtitle'],
          'amount'      => $rs['amount'],
          'time'        => $timeago,
          'status'      => $rs['status'],
      ];
  }

  // Leaderboard (top 20)
  $stmt = $db->prepare(
      'SELECT u.name, w.userid, w.usage_recent, w.upoint
         FROM wallets w
         INNER JOIN users u ON w.userid = u.id
        WHERE w.usage_recent > 0 AND w.repayscore >= 0
        ORDER BY w.usage_recent DESC
        LIMIT 20'
  );
  $stmt->execute([]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $leaderboard = [];
  $rank = 1;
  foreach ($rows as $rs) {
      $displayName = ($rs['userid'] == $uid) ? 'You' : $rs['name'];
      if ($rank === 1) { $icon = 'trophy'; }
      elseif ($rank === 2) { $icon = 'medal'; }
      elseif ($rank === 3) { $icon = 'award'; }
      else { $icon = 'user'; }
      $leaderboard[] = [
          'rank'   => $rank,
          'userid' => $rs['userid'],
          'icon'   => $icon,
          'name'   => $displayName,
          'score'  => $rs['usage_recent'],
      ];
      $rank++;
  }

  $data['wallet']       = $wallet;
  $data['transactions'] = $transactions;
  $data['leaderboard']  = $leaderboard;

  // Include services if client version is stale or missing
  if (!$clientVer || strtotime($clientVer) < strtotime('today')) {
      $cached = getCache($db, 'services_all');
      if ($cached) {
          $data['services'] = $cached;
      } else {
          $svc = json_decode(getAllServices($db), true);
          $data['services'] = $svc['data'] ?? null;
          if (!empty($svc['data'])) setCache($db, 'services_all', 'services', $svc['data']);
      }
  }

  sendJson(['status' => 'success', 'data' => $data]);
  