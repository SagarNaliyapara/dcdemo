-- =============================================================================
-- Migration: 003_align_orders_table_with_dc_only.sql
-- Database:  dc_cakephp
-- Purpose:
--   Align dc_cakephp.orders with dc-only.orders structure:
--     • Add missing columns (customer_id, ho_id, packsize, fileupload, etc.)
--     • Add matching indexes
--   dc_cakephp-specific columns are KEPT (approved_qty, flag, stock_status)
--   — they are required by notification_rules and scheduled_reports emails.
--   Zero data loss — existing rows are preserved.
--
-- Run: mysql -u root dc_cakephp < 003_align_orders_table_with_dc_only.sql
-- =============================================================================

USE dc_cakephp;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `orders`

  -- ── Customer link (matches dc-only.orders) ──────────────────────────────
  ADD COLUMN `customer_id`    INT UNSIGNED  NOT NULL DEFAULT 0   AFTER `id`,

  -- ── dc-only columns not previously in dc_cakephp ───────────────────────
  ADD COLUMN `ho_ordernumber` VARCHAR(255)  DEFAULT NULL         AFTER `ordernumber`,
  ADD COLUMN `ho_id`          INT           DEFAULT NULL         AFTER `ho_ordernumber`,
  ADD COLUMN `packsize`       VARCHAR(255)  DEFAULT NULL         AFTER `pipcode`,
  ADD COLUMN `fileupload`     TINYINT(1)    NOT NULL DEFAULT 0   AFTER `is_transmitted`,
  ADD COLUMN `auto`           TINYINT(1)    NOT NULL DEFAULT 0   AFTER `fileupload`,
  ADD COLUMN `RecordID`       INT           DEFAULT NULL         AFTER `auto`,
  ADD COLUMN `ftp_proces_id`  INT           NOT NULL DEFAULT 0   AFTER `RecordID`,
  ADD COLUMN `ruledesc`       VARCHAR(500)  NOT NULL DEFAULT ''  AFTER `notes`,
  ADD COLUMN `custom_tag`     VARCHAR(255)  NOT NULL DEFAULT ''  AFTER `ruledesc`,
  ADD COLUMN `proc_flag`      INT           NOT NULL DEFAULT 0   AFTER `custom_tag`,

  -- ── Indexes matching dc-only.orders ────────────────────────────────────
  ADD INDEX `customer_id`                (`customer_id`),
  ADD INDEX `ho_id`                      (`ho_id`),
  ADD INDEX `ho_ordernumber`             (`ho_ordernumber`),
  ADD INDEX `customer_opened_number`     (`customer_id`, `is_opened`, `order_number`(64)),
  ADD INDEX `customer_status_created`    (`customer_id`, `status`(32), `created`),
  ADD INDEX `order_customer_transmitted` (`order_number`, `customer_id`, `is_transmitted`),
  ADD INDEX `parent_customer_response`   (`parent_id`, `customer_id`, `response`(32), `transmit_method`(32));

SET FOREIGN_KEY_CHECKS = 1;

-- ── Final column list after migration ─────────────────────────────────────
-- id, customer_id, order_number, ordernumber, product_id, product_description,
-- pipcode, packsize, supplier_id, quantity, approved_qty(*), price, max_price,
-- dt_price, rule_price, parent_id, status, sent_date, is_opened,
-- is_transmitted, fileupload, auto, RecordID, ftp_proces_id, transmit_method,
-- transmit_date, orderdate, response, category, price_range, source, notes,
-- ruledesc, custom_tag, proc_flag, flag(*), stock_status(*), created, modified
-- (*) dc_cakephp-only columns kept for email attachment / UI features

SELECT 'Migration 003 complete — orders table aligned with dc-only' AS result;
SELECT COUNT(*) AS rows_preserved FROM orders;
