<?php

declare(strict_types=1);

use Core\Migrations\Migration;

return new class extends Migration {
    public function up(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS sys_shop (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_name VARCHAR(150) NOT NULL,
            shop_code VARCHAR(50) NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS prod_category (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            catname VARCHAR(150) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            eff_date DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS prod_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(255) NOT NULL,
            item_cat BIGINT UNSIGNED NULL,
            used_type VARCHAR(50) NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            eff_date DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_prod_items_category (item_cat)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_customer (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cus_name VARCHAR(200) NOT NULL,
            cus_mobile VARCHAR(30) NULL,
            cus_email VARCHAR(200) NULL,
            cus_address VARCHAR(255) NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_pos_mainsale (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_no VARCHAR(50) NOT NULL,
            customer_id BIGINT UNSIGNED NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            discount DECIMAL(12,2) NOT NULL DEFAULT 0,
            net_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            pay_type VARCHAR(30) NULL,
            sale_date DATETIME NOT NULL,
            cashier VARCHAR(100) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_bill_no (bill_no),
            INDEX idx_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_pos_billdetails (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_no VARCHAR(50) NOT NULL,
            item_id BIGINT UNSIGNED NULL,
            qty DECIMAL(10,2) NOT NULL DEFAULT 0,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_bill_no (bill_no),
            INDEX idx_item_id (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS cash_book (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            op_date DATE NOT NULL,
            shop VARCHAR(100) NULL,
            user VARCHAR(100) NULL,
            pay_type VARCHAR(50) NULL,
            remark VARCHAR(255) NULL,
            open_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
            cash_in DECIMAL(12,2) NOT NULL DEFAULT 0,
            cash_out DECIMAL(12,2) NOT NULL DEFAULT 0,
            close_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_op_date (op_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS cash_book');
        $db->exec('DROP TABLE IF EXISTS shop_pos_billdetails');
        $db->exec('DROP TABLE IF EXISTS shop_pos_mainsale');
        $db->exec('DROP TABLE IF EXISTS shop_customer');
        $db->exec('DROP TABLE IF EXISTS prod_items');
        $db->exec('DROP TABLE IF EXISTS prod_category');
        $db->exec('DROP TABLE IF EXISTS sys_shop');
    }
};
