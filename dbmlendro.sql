-- ============================================================
-- Lendro VTU API — Complete Database Schema
-- Engine: MySQL / MariaDB
-- Charset: utf8mb4
-- ============================================================
-- How to import:
--   mysql -u root -p dbmlendro < dbmlendro.sql
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `dbmlendro`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `dbmlendro`;

-- ── Users ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(120) NOT NULL,
  `phone`      VARCHAR(20)  DEFAULT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `status`     TINYINT(1)   DEFAULT 1  COMMENT '1=active, 0=disabled',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Wallets ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `wallets` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `userid`      INT(11)       NOT NULL,
  `balance`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `bucbalance`  DECIMAL(12,2)          DEFAULT 0.00  COMMENT 'bucket/bonus balance',
  `loanlimit`   DECIMAL(12,2)          DEFAULT 0.00,
  `loancount`   INT(11)                DEFAULT 0,
  `totalscore`  INT(11)                DEFAULT 0,
  `upoint`      INT(11)                DEFAULT 0     COMMENT 'total usage points ever',
  `usage_recent`INT(11)                DEFAULT 0     COMMENT 'usage points this period',
  `vscore`      INT(11)                DEFAULT 0     COMMENT 'verification score',
  `repayscore`  INT(11)                DEFAULT 0     COMMENT 'repayment score',
  `ctpoint`     INT(11)                DEFAULT 0     COMMENT 'community trust points',
  `plan`        VARCHAR(50)            DEFAULT NULL,
  `created_at`  TIMESTAMP              DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP              DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_userid` (`userid`),
  CONSTRAINT `fk_wallets_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Wallet audit log ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `wallet_logs` (
  `id`             INT(11)       NOT NULL AUTO_INCREMENT,
  `userid`         INT(11)       NOT NULL,
  `type`           ENUM('debit','credit') NOT NULL,
  `amount`         DECIMAL(12,2) NOT NULL,
  `balance_before` DECIMAL(12,2) NOT NULL,
  `balance_after`  DECIMAL(12,2) NOT NULL,
  `reference`      VARCHAR(100)  DEFAULT NULL,
  `description`    VARCHAR(255)  DEFAULT NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wallet_logs_userid` (`userid`),
  KEY `idx_wallet_logs_ref`    (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Providers ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `providers` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(100) DEFAULT NULL,
  `slug`           VARCHAR(50)  DEFAULT NULL  COMMENT 'unique short name used in code',
  `base_url`       VARCHAR(255) DEFAULT NULL,
  `api_key`        TEXT         DEFAULT NULL,
  `webhook_secret` VARCHAR(255) DEFAULT NULL  COMMENT 'used to verify incoming webhooks',
  `priority`       INT(11)      DEFAULT 1     COMMENT 'lower = tried first',
  `status`         TINYINT(1)   DEFAULT 1     COMMENT '1=active',
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed providers (replace api_key values before going live)
INSERT IGNORE INTO `providers` (`id`, `name`, `slug`, `base_url`, `api_key`, `priority`, `status`) VALUES
(1, 'CheapDataHub', 'cheapdatahub', 'https://www.cheapdatahub.ng/api/v1/resellers', 'YOUR_CHEAPDATAHUB_API_KEY', 1, 1),
(2, 'ConnectBridge', 'connectbridge', 'https://connectbridge.com.ng/api', 'YOUR_CONNECTBRIDGE_API_KEY', 2, 1);

-- ── Services (our normalised product catalogue) ───────────────────────────────
CREATE TABLE IF NOT EXISTS `services` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `service_key`  VARCHAR(120) DEFAULT NULL  COMMENT 'e.g. mtn_data_1gb_7day_sme',
  `name`         VARCHAR(150) NOT NULL      COMMENT 'human-readable label shown in UI',
  `network`      VARCHAR(50)  DEFAULT NULL  COMMENT 'mtn, glo, airtel, 9mobile, AEDC, …',
  `type`         ENUM('airtime','data','bill') DEFAULT NULL,
  `category`     VARCHAR(80)  DEFAULT NULL  COMMENT 'airtime|data|electricity|cable|education|betting',
  `price`        DECIMAL(10,2) DEFAULT NULL COMMENT 'our selling price (provider cost + markup)',
  `duration`     INT(11)       DEFAULT NULL COMMENT 'validity in days/weeks/months',
  `validity_unit` ENUM('day','week','month') DEFAULT 'day',
  `status`       TINYINT(1)   DEFAULT 1,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_key` (`service_key`),
  KEY `idx_services_type`    (`type`),
  KEY `idx_services_network` (`network`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Provider ↔ Service mapping ────────────────────────────────────────────────
-- Tells us which provider_code (SKU) to use when buying a service via a specific provider
CREATE TABLE IF NOT EXISTS `provider_services` (
  `id`            INT(11)       NOT NULL AUTO_INCREMENT,
  `provider_id`   INT(11)       DEFAULT NULL,
  `service_id`    INT(11)       DEFAULT NULL,
  `provider_code` VARCHAR(100)  DEFAULT NULL  COMMENT "provider's internal plan ID or SKU",
  `cost_price`    DECIMAL(10,2) DEFAULT NULL  COMMENT "provider's price before our markup",
  `priority`      INT(11)       DEFAULT 1     COMMENT 'lower = tried first when routing',
  `status`        TINYINT(1)    DEFAULT 1,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_service` (`provider_id`, `service_id`),
  CONSTRAINT `fk_ps_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers`(`id`),
  CONSTRAINT `fk_ps_service`  FOREIGN KEY (`service_id`)  REFERENCES `services`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transactions ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `transactions` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `userid`            INT(11)       NOT NULL,
  `service_id`        INT(11)       DEFAULT NULL,
  `provider_id`       INT(11)       DEFAULT NULL  COMMENT 'filled in after the worker picks a provider',
  `amount`            DECIMAL(10,2) NOT NULL,
  `phone`             VARCHAR(20)   DEFAULT NULL  COMMENT 'recipient phone number',
  `transtype`         ENUM('debit','credit') DEFAULT 'debit',
  `refno`             VARCHAR(100)  NOT NULL       COMMENT 'our reference: LDR-timestamp-uid',
  `idempotency_key`   VARCHAR(100)  DEFAULT NULL,
  `request_hash`      VARCHAR(64)   DEFAULT NULL   COMMENT 'sha256 of userid+service_id+phone',
  `transtitle`        VARCHAR(150)  DEFAULT NULL   COMMENT 'service name at time of purchase',
  `transdesc`         VARCHAR(255)  DEFAULT NULL,
  `service_type`      VARCHAR(50)   DEFAULT NULL   COMMENT 'airtime|data|electricity|cable|education',
  `status`            ENUM('pending','processing','success','failed','reversed','timeout') DEFAULT 'pending',
  `provider_status`   VARCHAR(50)   DEFAULT NULL   COMMENT "raw status string from the provider",
  `provider_reference`VARCHAR(100)  DEFAULT NULL   COMMENT "provider's own transaction ID",
  `provider_response` LONGTEXT      DEFAULT NULL   COMMENT 'raw JSON from provider',
  `callback_data`     LONGTEXT      DEFAULT NULL   COMMENT 'raw webhook payload from provider',
  `reconciled`        TINYINT(1)    DEFAULT 0      COMMENT '1 = reconciliation has finalised this tx',
  `completed_at`      DATETIME      DEFAULT NULL,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_refno`            (`refno`),
  KEY `idx_tx_userid`              (`userid`),
  KEY `idx_tx_status`              (`status`),
  KEY `idx_tx_idempotency`         (`idempotency_key`),
  CONSTRAINT `fk_tx_userid`   FOREIGN KEY (`userid`)   REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transaction Queue ─────────────────────────────────────────────────────────
-- The background worker picks jobs from here and processes them asynchronously
CREATE TABLE IF NOT EXISTS `transaction_queue` (
  `id`             INT(11)  NOT NULL AUTO_INCREMENT,
  `transaction_id` INT(11)  NOT NULL,
  `status`         ENUM('pending','processing','awaiting_reconciliation','awaiting_callback','completed','failed')
                            DEFAULT 'pending',
  `attempts`       INT(11)  DEFAULT 0     COMMENT 'how many times the worker has tried this job',
  `next_retry_at`  DATETIME DEFAULT NULL  COMMENT 'earliest time for the next retry',
  `locked_at`      DATETIME DEFAULT NULL  COMMENT 'when a worker claimed this job',
  `worker_token`   VARCHAR(50) DEFAULT NULL COMMENT 'which worker instance is processing this',
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_queue_status`       (`status`),
  KEY `idx_queue_next_retry`   (`next_retry_at`),
  KEY `idx_queue_tx_id`        (`transaction_id`),
  CONSTRAINT `fk_queue_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Provider Webhooks (raw callback log) ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `provider_callbacks` (
  `id`          INT(11)   NOT NULL AUTO_INCREMENT,
  `provider_id` INT(11)   NOT NULL,
  `reference`   VARCHAR(100) DEFAULT NULL,
  `payload`     LONGTEXT  NOT NULL  COMMENT 'raw JSON body received from provider',
  `status`      VARCHAR(50)  DEFAULT 'received',
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cb_reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Notifications ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT(11)  NOT NULL AUTO_INCREMENT,
  `userid`     INT(11)  NOT NULL,
  `message`    TEXT     NOT NULL,
  `status`     ENUM('unread','read') DEFAULT 'unread',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── API Response Cache ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `apicache` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `cachekey`   VARCHAR(100) NOT NULL,
  `cachegroup` VARCHAR(50)  DEFAULT NULL,
  `payload`    LONGTEXT     NOT NULL,
  `version`    VARCHAR(50)  DEFAULT NULL,
  `expires_at` DATETIME     DEFAULT NULL,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cachekey` (`cachekey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Commissions (profit tracking) ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `commissions` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `userid`          INT(11)       DEFAULT NULL,
  `requestid`       VARCHAR(100)  DEFAULT NULL,
  `prodtype`        VARCHAR(50)   DEFAULT NULL,
  `sprice`          DECIMAL(10,2) DEFAULT NULL  COMMENT 'selling price (what user paid)',
  `cprice`          DECIMAL(10,2) DEFAULT NULL  COMMENT 'cost price (what provider charged)',
  `commission_rate` DECIMAL(5,4)  DEFAULT NULL  COMMENT 'e.g. 0.15 for 15%',
  `supplier_cost`   DECIMAL(10,2) DEFAULT NULL,
  `commission`      DECIMAL(10,2) DEFAULT NULL  COMMENT 'profit on this transaction',
  `sprofit`         DECIMAL(10,2) DEFAULT NULL,
  `created_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_commissions_userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
