-- =============================================================================
-- Migration: 002_migrate_existing_user_data_to_customers.sql
-- Database:  dc_cakephp
-- Purpose:
--   Safe data migration for LIVE environments with existing data.
--   Handles the case where notification_rules.customer_id and
--   scheduled_reports.customer_id still hold OLD user_id values
--   (because the column was renamed from user_id → customer_id).
--
--   Steps:
--     1. Temporarily disable FK checks
--     2. Create a customer record for every user that doesn't have one
--     3. Link users.customer_id to their corresponding customer
--     4. Remap notification_rules.customer_id  (old user.id → customer.id)
--     5. Remap scheduled_reports.customer_id   (old user.id → customer.id)
--     6. Re-enable FK checks
--
-- Run: mysql -u root dc_cakephp < 002_migrate_existing_user_data_to_customers.sql
-- Safe to re-run (uses IF NOT EXISTS / WHERE guards).
-- =============================================================================

USE dc_cakephp;

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- STEP 1: Create a customer record for each user that doesn't have one yet
--         Match by email to avoid duplicates.
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO `customers` (
  `pharmacy`,
  `company_name`,
  `email`,
  `status`,
  `startdate`,
  `gphc_reg_number`,
  `order_number_count`,
  `act_time`,
  `created`,
  `modified`
)
SELECT
  u.`name`    AS pharmacy,
  u.`name`    AS company_name,
  u.`email`,
  1           AS status,
  ''          AS startdate,
  0           AS gphc_reg_number,
  1           AS order_number_count,
  0           AS act_time,
  COALESCE(u.`created`, NOW()),
  COALESCE(u.`modified`, NOW())
FROM `users` u
LEFT JOIN `customers` c ON c.`email` = u.`email`
WHERE c.`id` IS NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- STEP 2: Link users.customer_id → their matching customer (by email)
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE `users` u
JOIN   `customers` c ON c.`email` = u.`email`
SET    u.`customer_id` = c.`id`
WHERE  u.`customer_id` IS NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- STEP 3: Remap notification_rules.customer_id
--   Currently holds the OLD users.id value.
--   Map: notification_rules.customer_id (= old user.id)
--        → users.customer_id (= new customer.id)
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE `notification_rules` nr
JOIN   `users` u ON u.`id` = nr.`customer_id`
SET    nr.`customer_id` = u.`customer_id`
WHERE  u.`customer_id` IS NOT NULL
  AND  u.`customer_id` <> nr.`customer_id`;

-- ─────────────────────────────────────────────────────────────────────────────
-- STEP 4: Remap scheduled_reports.customer_id (same logic)
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE `scheduled_reports` sr
JOIN   `users` u ON u.`id` = sr.`customer_id`
SET    sr.`customer_id` = u.`customer_id`
WHERE  u.`customer_id` IS NOT NULL
  AND  u.`customer_id` <> sr.`customer_id`;

SET FOREIGN_KEY_CHECKS = 1;

-- ─────────────────────────────────────────────────────────────────────────────
-- Verify results
-- ─────────────────────────────────────────────────────────────────────────────
SELECT 'users → customers mapping' AS check_name;
SELECT u.id AS user_id, u.email, u.customer_id, c.company_name
FROM   `users` u
LEFT JOIN `customers` c ON c.id = u.customer_id;

SELECT 'notification_rules customer_id check' AS check_name;
SELECT nr.id, nr.customer_id, c.company_name
FROM   `notification_rules` nr
LEFT JOIN `customers` c ON c.id = nr.customer_id;

SELECT 'scheduled_reports customer_id check' AS check_name;
SELECT sr.id, sr.customer_id, c.company_name
FROM   `scheduled_reports` sr
LEFT JOIN `customers` c ON c.id = sr.customer_id;
