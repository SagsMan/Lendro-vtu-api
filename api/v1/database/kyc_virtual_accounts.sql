-- ─────────────────────────────────────────────────────────────────────────────
-- KYC and Virtual Accounts schema additions
-- Run once on the production DB to enable KYC verification and virtual accounts.
-- Safe to re-run — all statements use IF NOT EXISTS or IF NOT COLUMN.
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. Add kyc_status column to users table (if it doesn't already exist)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `kyc_status` ENUM('unverified','pending','verified','rejected')
    NOT NULL DEFAULT 'unverified'
    COMMENT 'KYC verification state' AFTER `status`;

-- 2. KYC submissions table — stores NIN / BVN per user
CREATE TABLE IF NOT EXISTS `user_kyc` (
  `id`           INT(11)       NOT NULL AUTO_INCREMENT,
  `userid`       INT(11)       NOT NULL COMMENT 'FK → users.id',
  `nin`          VARCHAR(11)   DEFAULT NULL COMMENT '11-digit National ID Number',
  `bvn`          VARCHAR(11)   DEFAULT NULL COMMENT '11-digit Bank Verification Number',
  `first_name`   VARCHAR(100)  DEFAULT NULL,
  `last_name`    VARCHAR(100)  DEFAULT NULL,
  `dob`          DATE          DEFAULT NULL COMMENT 'Date of birth YYYY-MM-DD',
  `status`       ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `submitted_at` DATETIME      DEFAULT NULL,
  `reviewed_at`  DATETIME      DEFAULT NULL,
  `notes`        TEXT          DEFAULT NULL COMMENT 'Admin or API rejection reason',
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP     NULL     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kyc_userid` (`userid`),
  KEY `idx_kyc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='NIN/BVN KYC submissions — one row per user';

-- 3. Virtual accounts table — SquadCo-assigned dedicated bank accounts
CREATE TABLE IF NOT EXISTS `virtual_accounts` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `userid`         INT(11)      NOT NULL COMMENT 'FK → users.id',
  `account_number` VARCHAR(20)  NOT NULL COMMENT 'The 10-digit virtual account number',
  `account_name`   VARCHAR(200) DEFAULT NULL,
  `bank_name`      VARCHAR(100) DEFAULT NULL COMMENT 'e.g. GTBank, Wema Bank',
  `bank_code`      VARCHAR(10)  DEFAULT NULL COMMENT 'CBN bank code',
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `provider`       VARCHAR(50)  NOT NULL DEFAULT 'squadco',
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NULL     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_va_userid` (`userid`),
  UNIQUE KEY `uq_va_account_number` (`account_number`),
  KEY `idx_va_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='SquadCo virtual accounts for wallet top-ups via bank transfer';

-- 4. Add wallet_logs table if it doesn't exist (needed by refundTransaction helper)
CREATE TABLE IF NOT EXISTS `wallet_logs` (
  `id`             INT(11)        NOT NULL AUTO_INCREMENT,
  `userid`         INT(11)        NOT NULL,
  `type`           ENUM('credit','debit') NOT NULL,
  `amount`         DECIMAL(12,2)  NOT NULL,
  `balance_before` DECIMAL(12,2)  NOT NULL,
  `balance_after`  DECIMAL(12,2)  NOT NULL,
  `reference`      VARCHAR(100)   DEFAULT NULL COMMENT 'FK → transactions.refno',
  `description`    VARCHAR(255)   DEFAULT NULL,
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wl_userid` (`userid`),
  KEY `idx_wl_reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Immutable audit log of every wallet credit and debit';

-- 5. Add worker_token and next_retry_at to transaction_queue (used by worker)
ALTER TABLE `transaction_queue`
  ADD COLUMN IF NOT EXISTS `worker_token`  VARCHAR(50)  DEFAULT NULL
    COMMENT 'Unique token of the worker instance that locked this job' AFTER `attempts`,
  ADD COLUMN IF NOT EXISTS `locked_at`     DATETIME     DEFAULT NULL
    COMMENT 'When the worker claimed this job' AFTER `worker_token`,
  ADD COLUMN IF NOT EXISTS `next_retry_at` DATETIME     DEFAULT NULL
    COMMENT 'Earliest time this job can be retried' AFTER `locked_at`;

-- 6. Add provider_ref and updated_at to transactions (used by worker result handlers)
ALTER TABLE `transactions`
  ADD COLUMN IF NOT EXISTS `provider_ref` VARCHAR(100) DEFAULT NULL
    COMMENT 'Provider-side transaction reference for reconciliation' AFTER `provider_id`,
  ADD COLUMN IF NOT EXISTS `updated_at`   TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    AFTER `created_at`;

-- 7. Ensure provider_services has a priority column (used by ServiceManager)
ALTER TABLE `provider_services`
  ADD COLUMN IF NOT EXISTS `priority`    INT(4)  NOT NULL DEFAULT 1
    COMMENT '1=primary, 2=fallback, etc.' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    AFTER `priority`;
