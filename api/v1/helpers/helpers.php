<?php
/**
 * helpers.php — General-purpose helper functions
 *
 * Keep these functions small and focused. If a function grows beyond ~30 lines
 * it probably belongs in a dedicated class instead.
 */

// ── JSON output ───────────────────────────────────────────────────────────────

/**
 * Echo a JSON response and optionally exit.
 * Pass $exit = false if you want to echo without stopping execution.
 */
function sendJson(array $data, int $httpCode = 200, bool $exit = true): void
{
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($exit) {
        exit;
    }
}

/**
 * Legacy alias kept for backwards-compatibility with old endpoint files.
 */
function toJSON($data, bool $echo = true): ?string
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($echo) {
        echo $json;
        return null;
    }
    return $json;
}

// ── Authentication guards ─────────────────────────────────────────────────────

/**
 * Abort with a 401 if the user is not logged in.
 * Call at the top of every protected endpoint.
 */
function requireAuth(): int
{
    if (empty($_SESSION['globaluid'])) {
        sendJson(['status' => 'failed', 'message' => 'You must be logged in to do that.'], 401);
    }
    return (int) $_SESSION['globaluid'];
}

// ── Reference / ID generation ─────────────────────────────────────────────────

/**
 * Generate a unique transaction reference number.
 * Format: LDR-1718400000-6642b3a12c4f7
 */
function generateRefNo(string $prefix = 'LDR'): string
{
    return strtoupper($prefix) . '-' . time() . '-' . substr(uniqid('', true), 0, 12);
}

// ── Phone number helpers ──────────────────────────────────────────────────────

/**
 * Normalise a Nigerian phone number to 10 digits (no leading 0, no country code).
 * e.g. +2348011111111 → 8011111111, 08011111111 → 8011111111
 */
function toPhone10(string $phone): string
{
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

    if (str_starts_with($phone, '+234')) {
        $phone = substr($phone, 4);
    } elseif (str_starts_with($phone, '234') && strlen($phone) === 13) {
        $phone = substr($phone, 3);
    }

    if (preg_match('/^\d{11}$/', $phone) && str_starts_with($phone, '0')) {
        $phone = substr($phone, 1);
    }

    return $phone;
}

/**
 * Detect which Nigerian network a phone number belongs to.
 * Returns the network slug ("mtn", "airtel", "glo", "9mobile") or false.
 */
function detectNetwork(string $phone): string|false
{
    // Normalise to 11-digit format starting with 0
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    if (str_starts_with($phone, '+234')) {
        $phone = '0' . substr($phone, 4);
    }
    if (str_starts_with($phone, '234') && strlen($phone) === 13) {
        $phone = '0' . substr($phone, 3);
    }
    if (preg_match('/^\d{10}$/', $phone)) {
        $phone = '0' . $phone;
    }

    $patterns = [
        'mtn'     => '/^(0703|0706|0803|0806|0810|0813|0814|0816|0903|0906|0913|0916)\d{7}$/',
        'airtel'  => '/^(0701|0708|0802|0808|0812|0901|0902|0904|0907|0912)\d{7}$/',
        'glo'     => '/^(0705|0805|0807|0811|0815|0905|0915)\d{7}$/',
        '9mobile' => '/^(0809|0817|0818|0909|0908)\d{7}$/',
    ];

    foreach ($patterns as $network => $pattern) {
        if (preg_match($pattern, $phone)) {
            return $network;
        }
    }

    return false;
}

// ── Wallet helpers ────────────────────────────────────────────────────────────

/**
 * Write a line to the wallet_logs table whenever money moves in or out.
 *
 * @param string $type  "debit" | "credit"
 */
function logWalletEvent(PDO $db, int $userid, string $type, float $amount, float $before, float $after, string $reference, string $description): void
{
    $stmt = $db->prepare(
        'INSERT INTO wallet_logs (userid, type, amount, balance_before, balance_after, reference, description, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$userid, $type, $amount, $before, $after, $reference, $description]);
}

/**
 * Refund a failed/reversed transaction back to the user's wallet.
 * Guards against double-refunds by checking the transaction status first.
 */
function refundTransaction(array $tx, PDO $db): bool
{
    try {
        $db->beginTransaction();

        // Lock the transaction row to prevent concurrent refunds
        $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? FOR UPDATE');
        $stmt->execute([$tx['id']]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            $db->rollBack();
            return false;
        }

        // Already refunded? Do nothing.
        if (in_array($transaction['status'], ['reversed', 'success'])) {
            $db->commit();
            return true;
        }

        // Lock the wallet
        $stmt = $db->prepare('SELECT * FROM wallets WHERE userid = ? FOR UPDATE');
        $stmt->execute([$tx['userid']]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            throw new Exception("Wallet not found for user {$tx['userid']}");
        }

        $before = (float) $wallet['balance'];
        $after  = $before + (float) $tx['amount'];

        // Credit back
        $stmt = $db->prepare('UPDATE wallets SET balance = ?, updated_at = NOW() WHERE userid = ?');
        $stmt->execute([$after, $tx['userid']]);

        logWalletEvent($db, $tx['userid'], 'credit', (float) $tx['amount'], $before, $after, $tx['refno'], 'Refund: transaction failed');

        // Mark the transaction as reversed
        $stmt = $db->prepare("UPDATE transactions SET status = 'reversed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$tx['id']]);

        $db->commit();
        return true;

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[refundTransaction] ' . $e->getMessage());
        return false;
    }
}

// ── Human-readable time ───────────────────────────────────────────────────────

function timeAgo(string $datetime): string
{
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return 'Unknown time';
    }

    $diff  = max(0, time() - $timestamp);
    $units = [
        31536000 => 'yr',
        2592000  => 'mth',
        604800   => 'wk',
        86400    => 'day',
        3600     => 'hr',
        60       => 'min',
        1        => 'sec',
    ];

    foreach ($units as $seconds => $label) {
        if ($diff >= $seconds) {
            $value = (int) floor($diff / $seconds);
            return "{$value} {$label}" . ($value > 1 ? 's' : '') . ' ago';
        }
    }

    return 'Just now';
}

// ── Service lookup ────────────────────────────────────────────────────────────

/**
 * Fetch all active services and return them grouped by type → network.
 * Shared by /client/services.php and any other endpoint that lists services.
 */
function getAllServices(PDO $db): string
  {
      $stmt = $db->prepare('SELECT * FROM services WHERE status = 1 ORDER BY type ASC, network ASC, price ASC');
      $stmt->execute();
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $airtimeNetworks = [];
      $dataNetworks    = [];
      $billCategories  = [];
      $seenAirtime = $seenData = $seenBill = [];

      $airtimeNames = [
          'mtn'=>'MTN Airtime VTU','airtel'=>'Airtel Airtime VTU',
          'glo'=>'GLO Airtime VTU','9mobile'=>'9mobile Airtime VTU','etisalat'=>'9mobile Airtime VTU',
      ];
      $catMap = [
          'electricity'=>['name'=>'Electricity Bill',            'identifier'=>'electricity-bill'],
          'cabletv'    =>['name'=>'TV Subscription',             'identifier'=>'tv-subscription'],
          'cable'      =>['name'=>'Cable Bill Payment',          'identifier'=>'tv-subscription'],
          'education'  =>['name'=>'Education',                   'identifier'=>'education'],
          'insurance'  =>['name'=>'Insurance',                   'identifier'=>'insurance'],
          'transport'  =>['name'=>'Transport and Logistics',     'identifier'=>'TRANSLOG'],
          'betting'    =>['name'=>'Betting & Entertainment',     'identifier'=>'DEALPAY'],
          'religion'   =>['name'=>'Religious Institutions',      'identifier'=>'RELINST'],
          'school'     =>['name'=>'Schools & Professional Bodies','identifier'=>'SCHPB'],
      ];

      foreach ($rows as $row) {
          $type     = strtolower($row['type']     ?? '');
          $network  = strtolower($row['network']  ?? '');
          $category = strtolower($row['category'] ?? '');

          if ($type === 'airtime' && $network && !in_array($network, $seenAirtime)) {
              $seenAirtime[]     = $network;
              $airtimeNetworks[] = [
                  'name'           => $airtimeNames[$network] ?? (strtoupper($network).' Airtime VTU'),
                  'serviceID'      => $network,
                  'maximum_amount' => null,
              ];
          }
          if ($type === 'data' && $network && !in_array($network, $seenData)) {
              $seenData[]     = $network;
              $dataNetworks[] = [
                  'name'           => $network.'-data',
                  'serviceID'      => $network.'-data',
                  'maximum_amount' => $row['price'] !== null ? (float)$row['price'] : null,
              ];
          }
          if ($type === 'bill' && $category && !in_array($category, $seenBill)) {
              $seenBill[]      = $category;
              $billCategories[] = [
                  'name'       => $catMap[$category]['name']       ?? ucfirst($category),
                  'identifier' => $catMap[$category]['identifier'] ?? $category,
              ];
          }
      }

      return json_encode([
          'status' => 'success',
          'data'   => [
              'categories' => $billCategories,
              'airtime'    => $airtimeNetworks,
              'data'       => $dataNetworks,
          ],
      ], JSON_UNESCAPED_UNICODE);
  }

  // ── Provider helper ───────────────────────────────────────────────────────────

/**
 * Resolve a provider's DB id from its slug.
 * Throws if the provider doesn't exist (so callers don't have to check).
 */
function getProviderId(string $slug, PDO $db): int
{
    $stmt = $db->prepare('SELECT id FROM providers WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception("Provider not found: {$slug}");
    }

    return (int) $row['id'];
}

  // ── Backward-compatibility aliases ───────────────────────────────────────────

  /**
   * Old auth guard used in legacy endpoint files.
   * Prefer requireAuth() in all new code.
   */
  function Securepg(): void
  {
      requireAuth();
  }

  /**
   * Alias for getAllServices — used in legacy home.php.
   */
  function getServices(array $opts = []): string
  {
      global $db;
      return getAllServices($db);
  }

  /**
   * Fetch services filtered by type / network / category.
   * Used by legacy show.php.
   */
  function getServicesBy(?string $type, ?string $network, ?string $category): string
    {
        global $db;
        $dbNetwork = $network;
        if ($network && str_ends_with($network, '-data')) $dbNetwork = substr($network, 0, -5);
        $sql = 'SELECT * FROM services WHERE status = 1';
        $params = [];
        if ($type)      { $sql .= ' AND type = ?';           $params[] = $type; }
        if ($dbNetwork) { $sql .= ' AND LOWER(network) = ?'; $params[] = strtolower($dbNetwork); }
        if ($category)  { $sql .= ' AND category = ?';       $params[] = $category; }
        $sql .= ' ORDER BY price ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $items = [];
        foreach ($rows as $row) {
            $days = (int)($row['duration'] ?? 0);
            $unit = $row['validity_unit'] ?? 'day';
            if ($unit === 'week')  $days *= 7;
            if ($unit === 'month') $days *= 30;
            $items[] = [
                'biller_name'     => $row['name'],
                'amount'          => $row['price'] !== null ? (float)$row['price'] : null,
                'validity_period' => $days,
                'group_name'      => strtoupper($row['network'] ?? '').' Data',
                'service_key'     => $row['service_key'] ?? '',
            ];
        }
        $billerCode = $network ?? $type;
        return json_encode(['status'=>'success','data'=>['dataitems'=>[$billerCode=>$items]]], JSON_UNESCAPED_UNICODE);
    }
  
