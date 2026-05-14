<?php

declare(strict_types=1);

use Core\Migrations\Migration;

return new class extends Migration {
    public function up(\PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS shop_stock_item (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id BIGINT UNSIGNED NULL,
            item_id BIGINT UNSIGNED NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            status TINYINT(1) NOT NULL DEFAULT 1,
            eff_date DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_shop_item (shop_id, item_id),
            INDEX idx_item_id (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_stock_imei (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            stock_item_id BIGINT UNSIGNED NULL,
            imei_no VARCHAR(100) NOT NULL,
            serial_no VARCHAR(100) NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            eff_date DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_imei_no (imei_no),
            INDEX idx_stock_item_id (stock_item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_rcv_stock (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id BIGINT UNSIGNED NULL,
            item_id BIGINT UNSIGNED NULL,
            ref_type VARCHAR(50) NULL,
            ref_no VARCHAR(100) NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            cost DECIMAL(12,2) NOT NULL DEFAULT 0,
            op_date DATETIME NOT NULL,
            operator VARCHAR(100) NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_shop_date (shop_id, op_date),
            INDEX idx_item_date (item_id, op_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_transmain (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            transfer_no VARCHAR(100) NOT NULL,
            from_shop BIGINT UNSIGNED NULL,
            to_shop BIGINT UNSIGNED NULL,
            transfer_date DATETIME NOT NULL,
            transfer_status VARCHAR(40) NOT NULL DEFAULT 'PENDING',
            transfer_by VARCHAR(100) NULL,
            receive_by VARCHAR(100) NULL,
            note VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_transfer_no (transfer_no),
            INDEX idx_transfer_date (transfer_date),
            INDEX idx_shop_pair (from_shop, to_shop)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_translog (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            transfer_id BIGINT UNSIGNED NULL,
            transfer_no VARCHAR(100) NULL,
            item_id BIGINT UNSIGNED NULL,
            imei_no VARCHAR(100) NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
            line_status VARCHAR(40) NOT NULL DEFAULT 'PENDING',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_transfer_id (transfer_id),
            INDEX idx_transfer_no (transfer_no),
            INDEX idx_item_id (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_transfer_bin (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            transfer_id BIGINT UNSIGNED NULL,
            transfer_no VARCHAR(100) NULL,
            stock_item_id BIGINT UNSIGNED NULL,
            imei_no VARCHAR(100) NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            from_shop BIGINT UNSIGNED NULL,
            to_shop BIGINT UNSIGNED NULL,
            action_type VARCHAR(30) NOT NULL DEFAULT 'OUT',
            op_date DATETIME NOT NULL,
            operator VARCHAR(100) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_transfer_bin_transfer (transfer_id),
            INDEX idx_transfer_bin_item (stock_item_id),
            INDEX idx_transfer_bin_date (op_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_return_main (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            return_no VARCHAR(100) NOT NULL,
            bill_no VARCHAR(100) NULL,
            customer_id BIGINT UNSIGNED NULL,
            return_date DATETIME NOT NULL,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_return_no (return_no),
            INDEX idx_bill_no (bill_no),
            INDEX idx_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_return_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            return_id BIGINT UNSIGNED NULL,
            return_no VARCHAR(100) NULL,
            item_id BIGINT UNSIGNED NULL,
            imei_no VARCHAR(100) NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_return_id (return_id),
            INDEX idx_return_no (return_no),
            INDEX idx_item_id (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_sales_return (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_no VARCHAR(100) NULL,
            item_id BIGINT UNSIGNED NULL,
            imei_no VARCHAR(100) NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            return_date DATETIME NOT NULL,
            operator VARCHAR(100) NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_bill_no (bill_no),
            INDEX idx_item_id (item_id),
            INDEX idx_return_date (return_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_edit_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id BIGINT UNSIGNED NULL,
            item_id BIGINT UNSIGNED NULL,
            imei_no VARCHAR(100) NULL,
            old_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            new_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            reason VARCHAR(255) NULL,
            sys_recorder VARCHAR(100) NULL,
            op_date DATETIME NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_edit_shop_item (shop_id, item_id),
            INDEX idx_edit_date (op_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_delete_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id BIGINT UNSIGNED NULL,
            item_id BIGINT UNSIGNED NULL,
            imei_no VARCHAR(100) NULL,
            deleted_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            reason VARCHAR(255) NULL,
            sys_recorder VARCHAR(100) NULL,
            op_date DATETIME NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_delete_shop_item (shop_id, item_id),
            INDEX idx_delete_date (op_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_discard_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id BIGINT UNSIGNED NULL,
            item_id BIGINT UNSIGNED NULL,
            imei_no VARCHAR(100) NULL,
            discard_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            reason VARCHAR(255) NULL,
            sys_recorder VARCHAR(100) NULL,
            op_date DATETIME NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_discard_shop_item (shop_id, item_id),
            INDEX idx_discard_date (op_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(\PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS stock_discard_log');
        $db->exec('DROP TABLE IF EXISTS stock_delete_log');
        $db->exec('DROP TABLE IF EXISTS stock_edit_log');
        $db->exec('DROP TABLE IF EXISTS shop_sales_return');
        $db->exec('DROP TABLE IF EXISTS stock_return_log');
        $db->exec('DROP TABLE IF EXISTS stock_return_main');
        $db->exec('DROP TABLE IF EXISTS stock_transfer_bin');
        $db->exec('DROP TABLE IF EXISTS stock_translog');
        $db->exec('DROP TABLE IF EXISTS stock_transmain');
        $db->exec('DROP TABLE IF EXISTS shop_rcv_stock');
        $db->exec('DROP TABLE IF EXISTS shop_stock_imei');
        $db->exec('DROP TABLE IF EXISTS shop_stock_item');
    }
};
