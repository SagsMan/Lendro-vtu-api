<?php
/**
 * POST /api/v1/auth/kyc
 *
 * KYC (Know Your Customer) verification — submit NIN and/or BVN.
 * After successful KYC, a SquadCo virtual account is automatically created
 * so the user can deposit funds without using the payment widget.
 *
 * Steps:
 *  1. Validate inputs
 *  2. Check if already verified
 *  3. Store KYC data + mark pending
 *  4. Verify NIN/BVN with SquadCo identity API
 *  5. Update kyc_status in users table
 *  6. Create virtual account (see accounts/virtual-account.php logic)
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['status' => 'failed', 'message' => 'Method not allowed.'], 405);
}

$userid = requireAuth();

$nin    = trim($_POST['nin'] ?? '');
$bvn    = trim($_POST['bvn'] ?? '');
$fname  = trim($_POST['first_name'] ?? '');
$lname  = trim($_POST['last_name']  ?? '');
$dob    = trim($_POST['dob']        ?? ''); // YYYY-MM-DD

// ── Validation ────────────────────────────────────────────────────────────────

if (empty($nin) && empty($bvn)) {
    sendJson(['status' => 'failed', 'message' => 'Please provide your NIN or BVN.'], 422);
}
if (!empty($nin) && !preg_match('/^\d{11}$/', $nin)) {
    sendJson(['status' => 'failed', 'message' => 'NIN must be exactly 11 digits.'], 422);
}
if (!empty($bvn) && !preg_match('/^\d{11}$/', $bvn)) {
    sendJson(['status' => 'failed', 'message' => 'BVN must be exactly 11 digits.'], 422);
}

// ── Already verified? ─────────────────────────────────────────────────────────

$stmt = $db->prepare('SELECT kyc_status FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user['kyc_status'] === 'verified') {
    sendJson(['status' => 'already_verified', 'message' => 'Your identity has already been verified.']);
}

// ── Upsert KYC record (pending) ───────────────────────────────────────────────

$stmt = $db->prepare(
    'INSERT INTO user_kyc (userid, nin, bvn, first_name, last_name, dob, status, submitted_at)
     VALUES (?, ?, ?, ?, ?, ?, \'pending\', NOW())
     ON DUPLICATE KEY UPDATE
       nin = VALUES(nin),
       bvn = VALUES(bvn),
       first_name = VALUES(first_name),
       last_name  = VALUES(last_name),
       dob        = VALUES(dob),
       status     = \'pending\',
       submitted_at = NOW()'
);
$stmt->execute([$userid, $nin ?: null, $bvn ?: null, $fname ?: null, $lname ?: null, $dob ?: null]);

// ── Verify with SquadCo Identity API ─────────────────────────────────────────

$verifyEndpoint = rtrim($squard_Endpoint, '/');
$verifySuccess  = false;
$verifyPayload  = [];
$verifyError    = '';

try {
    if (!empty($nin)) {
        $verifyPayload = ['id_type' => 'NIN', 'id_number' => $nin];
    } elseif (!empty($bvn)) {
        $verifyPayload = ['id_type' => 'BVN', 'id_number' => $bvn];
    }

    $ch = curl_init($verifyEndpoint . '/identity/verify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($verifyPayload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $squad_SecretKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw       = curl_exec($ch);
    $curlErr   = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("cURL error: {$curlErr}");
    }

    $res = json_decode($raw, true);
    if (!$res) {
        throw new Exception("Invalid JSON from identity API.");
    }

    $apiStatus = (int) ($res['status'] ?? 0);

    if ($apiStatus === 200 || ($res['success'] ?? false)) {
        $verifySuccess = true;
    } else {
        $verifyError = $res['message'] ?? 'Identity verification failed.';
    }

} catch (Exception $e) {
    error_log('[kyc] Verification error: ' . $e->getMessage());
    // On network failure, queue for manual review instead of hard-failing
    $verifyError = 'Verification service unavailable. Your KYC has been submitted for manual review.';
}

// ── Update KYC + user status ──────────────────────────────────────────────────

$kycStatus = $verifySuccess ? 'verified' : 'pending';

$stmt = $db->prepare(
    "UPDATE user_kyc SET status = ?, reviewed_at = NOW() WHERE userid = ?"
);
$stmt->execute([$kycStatus, $userid]);

$stmt = $db->prepare(
    "UPDATE users SET kyc_status = ? WHERE id = ?"
);
$stmt->execute([$kycStatus, $userid]);

// ── Auto-create virtual account on successful KYC ─────────────────────────────

$virtualAccount = null;
if ($verifySuccess) {
    try {
        // Fetch user details for virtual account creation
        $stmt = $db->prepare('SELECT email, fullname, phone FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userid]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if virtual account already exists
        $stmt = $db->prepare('SELECT account_number FROM virtual_accounts WHERE userid = ? LIMIT 1');
        $stmt->execute([$userid]);
        $existingVA = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingVA && $userData) {
            $nameParts = explode(' ', trim($userData['fullname'] ?? 'Lendro User'), 2);
            $vaPayload = [
                'first_name'      => $nameParts[0] ?? 'Lendro',
                'last_name'       => $nameParts[1] ?? 'User',
                'mobile_num'      => '0' . toPhone10($userData['phone'] ?? ''),
                'email'           => $userData['email'] ?? '',
                'bvn'             => $bvn ?: '',
                'nin'             => $nin ?: '',
                'is_permanent'    => true,
                'customer_identifier' => 'lendro_' . $userid,
            ];

            $ch = curl_init(rtrim($squard_Endpoint, '/') . '/virtual-account/create');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($vaPayload),
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $squad_SecretKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT        => 30,
            ]);
            $vaRaw = curl_exec($ch);
            curl_close($ch);

            $vaRes = json_decode($vaRaw, true);

            if ((int) ($vaRes['status'] ?? 0) === 200 && !empty($vaRes['data']['virtual_account_number'])) {
                $vaData = $vaRes['data'];
                $stmt = $db->prepare(
                    'INSERT INTO virtual_accounts
                        (userid, account_number, account_name, bank_name, bank_code, created_at)
                     VALUES (?, ?, ?, ?, ?, NOW())'
                );
                $stmt->execute([
                    $userid,
                    $vaData['virtual_account_number'],
                    $vaData['customer_name'] ?? ($vaPayload['first_name'] . ' ' . $vaPayload['last_name']),
                    $vaData['bank_name']     ?? 'GTBank',
                    $vaData['bank_code']     ?? '',
                ]);

                $virtualAccount = [
                    'account_number' => $vaData['virtual_account_number'],
                    'account_name'   => $vaData['customer_name'] ?? '',
                    'bank_name'      => $vaData['bank_name'] ?? 'GTBank',
                ];
            }
        } elseif ($existingVA) {
            $virtualAccount = [
                'account_number' => $existingVA['account_number'],
                'account_name'   => '',
                'bank_name'      => '',
            ];
        }
    } catch (Exception $e) {
        error_log('[kyc] Virtual account creation error: ' . $e->getMessage());
        // Not fatal — KYC still succeeded
    }
}

// ── Response ──────────────────────────────────────────────────────────────────

if ($verifySuccess) {
    sendJson([
        'status'          => 'success',
        'kyc_status'      => 'verified',
        'message'         => 'Identity verified successfully.',
        'virtual_account' => $virtualAccount,
    ]);
} else {
    sendJson([
        'status'     => 'pending',
        'kyc_status' => 'pending',
        'message'    => $verifyError ?: 'KYC submitted and is under review.',
    ]);
}
