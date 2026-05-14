<?php

declare(strict_types=1);

use Core\Migrations\Migration;

return new class extends Migration {
    public function up(\PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS shop_supplier (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sup_name VARCHAR(200) NOT NULL,
            sup_mobile VARCHAR(30) NULL,
            sup_email VARCHAR(200) NULL,
            sup_address VARCHAR(255) NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_grnmain (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            grn_no VARCHAR(100) NOT NULL,
            supplier_id BIGINT UNSIGNED NULL,
            shop_id BIGINT UNSIGNED NULL,
            grn_date DATETIME NOT NULL,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            discount DECIMAL(12,2) NOT NULL DEFAULT 0,
            grand_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            due_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_grn_no (grn_no),
            INDEX idx_supplier_id (supplier_id),
            INDEX idx_shop_id (shop_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_grnitem (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            grn_no VARCHAR(100) NOT NULL,
            item_id BIGINT UNSIGNED NULL,
            imei_no VARCHAR(100) NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            sell_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_grn_no (grn_no),
            INDEX idx_item_id (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_grn_pay (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            grn_no VARCHAR(100) NOT NULL,
            pay_date DATETIME NOT NULL,
            pay_type VARCHAR(40) NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            reference_no VARCHAR(100) NULL,
            recorder VARCHAR(100) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_grn_pay_grn_no (grn_no),
            INDEX idx_grn_pay_date (pay_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_grn_pay_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            grn_no VARCHAR(100) NOT NULL,
            pay_id BIGINT UNSIGNED NULL,
            old_due DECIMAL(12,2) NOT NULL DEFAULT 0,
            paid DECIMAL(12,2) NOT NULL DEFAULT 0,
            new_due DECIMAL(12,2) NOT NULL DEFAULT 0,
            op_date DATETIME NOT NULL,
            recorder VARCHAR(100) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_grn_log_grn_no (grn_no),
            INDEX idx_grn_log_pay_id (pay_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS grn_main (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            grn_no VARCHAR(100) NOT NULL,
            sup_name VARCHAR(200) NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_grn_main_no (grn_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS grn_temp_item (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            grn_no VARCHAR(100) NOT NULL,
            item_id BIGINT UNSIGNED NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_grn_temp_no (grn_no),
            INDEX idx_grn_temp_item (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS supplier_detail (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            supplier_id BIGINT UNSIGNED NULL,
            detail_key VARCHAR(100) NOT NULL,
            detail_value VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_supplier_detail_supplier (supplier_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(\PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS supplier_detail');
        $db->exec('DROP TABLE IF EXISTS grn_temp_item');
        $db->exec('DROP TABLE IF EXISTS grn_main');
        $db->exec('DROP TABLE IF EXISTS shop_grn_pay_log');
        $db->exec('DROP TABLE IF EXISTS shop_grn_pay');
        $db->exec('DROP TABLE IF EXISTS shop_grnitem');
        $db->exec('DROP TABLE IF EXISTS shop_grnmain');
        $db->exec('DROP TABLE IF EXISTS shop_supplier');
    }
};
