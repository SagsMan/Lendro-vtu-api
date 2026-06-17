<?php
  /**
   * Lendro VTU API — Database Bootstrap
   *
   * Uses persistent PDO connections so PHP-FPM workers reuse
   * the same MySQL connection across requests, keeping the
   * active connection count bounded by PHP-FPM worker count
   * rather than by concurrent requests.
   */

  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }

  require_once __DIR__ . '/configs.php';

  function lendro_db_connect(string $host, string $dbname, string $username, string $password, int $attempt = 1): PDO {
      try {
          return new PDO(
              "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
              $username,
              $password,
              [
                  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                  PDO::ATTR_EMULATE_PREPARES   => false,
                  PDO::ATTR_PERSISTENT         => true,   // reuse per-worker connection
                  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
              ]
          );
      } catch (PDOException $e) {
          // max_user_connections or gone-away: wait briefly and retry once
          if ($attempt < 3 && in_array($e->getCode(), [1203, 2006, 2013])) {
              usleep(300000 * $attempt); // 0.3s, 0.6s
              return lendro_db_connect($host, $dbname, $username, $password, $attempt + 1);
          }
          error_log('[DB] Connection failed (attempt ' . $attempt . '): ' . $e->getMessage());
          http_response_code(503);
          echo json_encode(['status' => 'error', 'message' => 'Database unavailable. Please try again later.']);
          exit;
      }
  }

  $db = lendro_db_connect($host, $dbname, $username, $password);

  require_once __DIR__ . '/helpers/helpers.php';
  require_once __DIR__ . '/helpers/fxn-general.php';
  