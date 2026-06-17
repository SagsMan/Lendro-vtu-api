<?php
  /**
   * Background Worker: Process Transaction Queue
   *
   * NOTE FOR SHARED HOSTING: This worker should be run via cron (every 1-2 minutes)
   * rather than as a long-running daemon. It processes all pending jobs in one pass
   * then exits, so it doesn't hold MySQL connections open indefinitely.
   *
   * Cron schedule: * * * * * php /home/tracsmda/lendro/api/v1/workers/process_transactions.php >> /dev/null 2>&1
   */

  require_once __DIR__ . '/../db.php';
  require_once __DIR__ . '/../ServiceManager.php';
  require_once __DIR__ . '/../ProviderFactory.php';
  require_once __DIR__ . '/../ProviderResponseNormalizer.php';
  require_once __DIR__ . '/../helpers/helpers.php';
  require_once __DIR__ . '/../helpers/QueueHelper.php';

  $workerToken = 'wkr_' . uniqid();
  $maxRetries  = 3;
  $processed   = 0;
  $maxBatch    = 20; // process up to 20 jobs per cron run then exit

  echo "[{$workerToken}] Transaction worker started.\n";

  // Process jobs in a bounded loop — exit when done (cron restarts us)
  while ($processed < $maxBatch) {
      try {
          $db->beginTransaction();
          $stmt = $db->prepare(
              "SELECT * FROM transaction_queue
                WHERE status = 'pending'
                  AND (next_retry_at IS NULL OR next_retry_at <= NOW())
                ORDER BY id ASC
                LIMIT 1
                FOR UPDATE SKIP LOCKED"
          );
          $stmt->execute();
          $job = $stmt->fetch(PDO::FETCH_ASSOC);

          if (!$job) {
              $db->commit();
              break; // no more work — exit cleanly
          }

          $stmt = $db->prepare(
              "UPDATE transaction_queue SET status = 'processing', locked_at = NOW(), worker_token = ? WHERE id = ?"
          );
          $stmt->execute([$workerToken, $job['id']]);
          $db->commit();

          $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
          $stmt->execute([$job['transaction_id']]);
          $tx = $stmt->fetch(PDO::FETCH_ASSOC);

          if (!$tx) {
              echo "Job #{$job['id']}: transaction missing — skipping.\n";
              $processed++;
              continue;
          }

          echo "Processing job #{$job['id']} | ref: {$tx['refno']}\n";

          $providers = ServiceManager::getAllProviders((int) $tx['service_id'], $db);

          if (empty($providers)) {
              QueueHelper::refundAndFail($job['id'], $tx, $db, 'No providers available for this service');
              $processed++;
              continue;
          }

          $success = false;
          foreach ($providers as $providerRow) {
              try {
                  $provider = ProviderFactory::make($providerRow['slug'], $db);
                  $result   = $provider->purchase($tx, $db);
                  $norm     = ProviderResponseNormalizer::normalize($providerRow['slug'], $result);

                  if ($norm['status'] === 'success') {
                      QueueHelper::markSuccess($job['id'], $tx['id'], $norm, $db);
                      $success = true;
                      break;
                  } elseif ($norm['status'] === 'pending') {
                      QueueHelper::markAwaitingReconciliation($job['id'], $tx['id'], $norm, $db);
                      $success = true;
                      break;
                  }
              } catch (Exception $e) {
                  echo "Provider {$providerRow['slug']} failed: " . $e->getMessage() . "\n";
              }
          }

          if (!$success) {
              QueueHelper::refundAndFail($job['id'], $tx, $db, 'All providers failed');
          }

          $processed++;

      } catch (PDOException $e) {
          echo "DB error: " . $e->getMessage() . "\n";
          try { $db->rollBack(); } catch (Exception $ex) {}
          break;
      }
  }

  echo "[{$workerToken}] Done. Processed {$processed} jobs.\n";
  // $db goes out of scope here — connection returned to pool
  