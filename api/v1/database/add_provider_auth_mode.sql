-- Add auth_mode column to providers table for ConnectBridge Token auth
ALTER TABLE `providers`
  ADD COLUMN IF NOT EXISTS `auth_mode` VARCHAR(20) NOT NULL DEFAULT 'bearer'
  AFTER `api_key`;

-- Update ConnectBridge provider to use Token auth
UPDATE `providers` SET `auth_mode` = 'token' WHERE `slug` = 'connectbridge';

-- Confirm the update
SELECT id, name, slug, auth_mode FROM providers;
