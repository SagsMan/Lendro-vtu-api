<?php
/**
 * Lendro VTU API — Database Bootstrap
 *
 * Starts the session, loads config, and opens a PDO connection.
 * Every endpoint that needs the database just does: require_once __DIR__ . '/db.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/configs.php';

try {
    $db = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // always return associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // use real prepared statements
        ]
    );
} catch (PDOException $e) {
    // Never expose the real message in production — log it, return JSON error
    error_log('[DB] Connection failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Database unavailable. Please try again later.']);
    exit;
}

require_once __DIR__ . '/helpers/helpers.php';
require_once __DIR__ . '/helpers/fxn-general.php';
