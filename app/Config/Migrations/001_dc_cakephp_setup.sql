-- =============================================================================
-- Migration: 001_dc_cakephp_setup.sql
-- Database:  dc_cakephp  (standalone — no cross-database links)
-- Purpose:
--   1. Create `customers` table (mirrors dc-only.customers)
--   2. Add FK: notification_rules.customer_id  → customers.id
--   3. Add FK: scheduled_reports.customer_id   → customers.id
--   4. Add customer_id column to users table
--
-- Run order on a LIVE server with existing data:
--   Step 1: mysql -u root dc_cakephp < 001_dc_cakephp_setup.sql
--   Step 2: mysql -u root dc_cakephp < 002_migrate_existing_user_data_to_customers.sql
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

USE dc_cakephp;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. customers table (same structure as dc-only.customers)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `customers` (
  `id`                            int unsigned      NOT NULL AUTO_INCREMENT,
  `pharmacy`                      varchar(255)      DEFAULT NULL,
  `company_name`                  varchar(255)      DEFAULT NULL,
  `gphc_reg_number`               int               NOT NULL DEFAULT '0',
  `groupid`                       varchar(255)      DEFAULT NULL,
  `bgroupid`                      varchar(255)      DEFAULT NULL,
  `ipgroup`                       varchar(255)      DEFAULT NULL,
  `account_number`                varchar(100)      DEFAULT NULL,
  `odscode`                       varchar(255)      DEFAULT NULL,
  `order_number_count`            bigint            NOT NULL DEFAULT '1',
  `vat_reg_num`                   varchar(100)      DEFAULT NULL,
  `company_reg_num`               varchar(100)      DEFAULT NULL,
  `email`                         varchar(255)      DEFAULT NULL,
  `secondary_email`               varchar(100)      DEFAULT NULL,
  `phone`                         varchar(20)       DEFAULT NULL,
  `responsible_person`            varchar(100)      DEFAULT NULL,
  `phone_responsible_person`      bigint            DEFAULT NULL,
  `member_of_buying_group`        varchar(100)      DEFAULT NULL,
  `reg_active_wholesale_suppliers` varchar(255)     DEFAULT NULL,
  `fax`                           varchar(20)       DEFAULT NULL,
  `address`                       varchar(255)      DEFAULT NULL,
  `address2`                      varchar(255)      DEFAULT NULL,
  `area`                          varchar(255)      DEFAULT NULL,
  `city`                          varchar(255)      DEFAULT NULL,
  `postcode`                      varchar(255)      DEFAULT NULL,
  `password`                      varchar(300)      DEFAULT NULL,
  `encpassword`                   varchar(100)      NOT NULL DEFAULT '',
  `activate_code`                 varchar(255)      DEFAULT NULL,
  `act_time`                      int               NOT NULL DEFAULT '0',
  `status`                        tinyint(1)        NOT NULL DEFAULT '0',
  `role`                          enum('admin','user') DEFAULT 'user',
  `notes`                         text,
  `sigma_acc`                     varchar(255)      DEFAULT NULL,
  `alliance_acc`                  varchar(255)      DEFAULT NULL,
  `otc_acc`                       varchar(255)      DEFAULT NULL,
  `cavendish_acc`                 varchar(255)      DEFAULT NULL,
  `cav_sap`                       varchar(255)      DEFAULT NULL,
  `med_mnh`                       varchar(255)      DEFAULT NULL,
  `new_customer`                  tinyint(1)        NOT NULL DEFAULT '0',
  `last_login`                    varchar(255)      DEFAULT NULL,
  `enable_autoorder`              int               NOT NULL DEFAULT '0',
  `demo_account`                  int               NOT NULL DEFAULT '0',
  `IP_ENABLE`                     int               NOT NULL DEFAULT '1',
  `hideprice`                     int               NOT NULL DEFAULT '0',
  `ohistory_pricehide`            int               NOT NULL DEFAULT '0',
  `hide_price`                    int               NOT NULL DEFAULT '0',
  `hideprice_orderpad`            int               DEFAULT '0',
  `pricerange`                    int               NOT NULL DEFAULT '0',
  `excess_stock`                  int               NOT NULL DEFAULT '0',
  `pmr`                           tinyint(1)        NOT NULL DEFAULT '0',
  `refresh`                       int               NOT NULL DEFAULT '0',
  `logout`                        int               NOT NULL DEFAULT '0',
  `max_price`                     int               NOT NULL DEFAULT '0',
  `tariff_report`                 tinyint           DEFAULT NULL,
  `tariff_report_valid_till`      date              DEFAULT NULL,
  `tariff_report_subscribe`       tinyint           DEFAULT '0',
  `ftp_as_pmr`                    int               NOT NULL DEFAULT '0',
  `pmr_as_ftp`                    int               NOT NULL DEFAULT '0',
  `ftp_res_cust`                  int               NOT NULL DEFAULT '0',
  `secondary_db`                  int               NOT NULL DEFAULT '0',
  `prange`                        int               NOT NULL DEFAULT '0',
  `supp_mgment`                   int               NOT NULL DEFAULT '1',
  `supp_priority`                 int               NOT NULL DEFAULT '1',
  `pmr_system`                    varchar(255)      DEFAULT NULL,
  `pmr_setup_type`                varchar(255)      DEFAULT NULL,
  `graph`                         int               NOT NULL DEFAULT '0',
  `stock`                         int               NOT NULL DEFAULT '1',
  `psot`                          tinyint(1)        NOT NULL DEFAULT '1',
  `user_ip`                       varchar(255)      DEFAULT NULL,
  `packoptorder`                  tinyint(1)        NOT NULL DEFAULT '0',
  `switchparent`                  tinyint(1)        NOT NULL DEFAULT '0',
  `ftp_ceg_nex`                   tinyint           NOT NULL DEFAULT '0',
  `dcftp`                         int               NOT NULL DEFAULT '0',
  `cegidem`                       int               NOT NULL DEFAULT '0',
  `returnmg`                      int               NOT NULL DEFAULT '0',
  `prd_rule`                      int               NOT NULL DEFAULT '0',
  `brandparent`                   tinyint(1)        NOT NULL DEFAULT '0',
  `alliance_solus`                int               DEFAULT '0',
  `aah_solus`                     int               NOT NULL DEFAULT '0',
  `phoienix_solus`                int               NOT NULL DEFAULT '0',
  `reason_for_leaving`            varchar(255)      DEFAULT NULL,
  `startdate`                     varchar(255)      NOT NULL DEFAULT '',
  `deactive`                      bigint            NOT NULL DEFAULT '0',
  `f_10_integration`              varchar(255)      DEFAULT NULL,
  `dc_pharmacy_portal`            int               DEFAULT '0',
  `created`                       datetime          DEFAULT NULL,
  `created_by`                    int               NOT NULL DEFAULT '0',
  `modified`                      datetime          DEFAULT NULL,
  `passwordlastmodified`          varchar(255)      NOT NULL DEFAULT '0',
  `updatepasswordmodalstatus`     int               NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `account_number` (`account_number`),
  KEY `groupid`        (`groupid`),
  KEY `bgroupid`       (`bgroupid`),
  KEY `status`         (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. Add FK: notification_rules.customer_id → customers.id
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE `notification_rules`
  ADD CONSTRAINT `fk_notification_rules_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. Add FK: scheduled_reports.customer_id → customers.id
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE `scheduled_reports`
  ADD CONSTRAINT `fk_scheduled_reports_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. Add customer_id to users table (links auth user to a customer)
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE `users`
  ADD COLUMN `customer_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`,
  ADD CONSTRAINT `fk_users_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
