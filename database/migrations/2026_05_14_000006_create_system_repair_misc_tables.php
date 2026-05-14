<?php

declare(strict_types=1);

use Core\Migrations\Migration;

return new class extends Migration {
    public function up(\PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS sys_company (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(200) NOT NULL,
            company_address VARCHAR(255) NULL,
            company_phone VARCHAR(30) NULL,
            company_email VARCHAR(200) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS sys_user (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(150) NULL,
            user_role VARCHAR(100) NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS sys_privilege (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            privilegename VARCHAR(150) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS sys_privilegemap (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            mapid VARCHAR(100) NOT NULL,
            linkdata VARCHAR(150) NOT NULL,
            linkvalue VARCHAR(150) NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_privmap_mapid (mapid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS prod_item_color (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            color_name VARCHAR(100) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            eff_date DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_rcv_cards (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cat_id BIGINT UNSIGNED NULL,
            prod_id BIGINT UNSIGNED NULL,
            card_name VARCHAR(150) NOT NULL,
            operator VARCHAR(100) NULL,
            remark VARCHAR(255) NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            eff_date DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_rcv_operator (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            operator_name VARCHAR(150) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            eff_date DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS cashier_point_control (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            shop_id BIGINT UNSIGNED NULL,
            op_date DATE NOT NULL,
            open_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
            close_balance DECIMAL(12,2) NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'OPEN',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS cashier_point_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            point_id BIGINT UNSIGNED NULL,
            op_date DATETIME NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            action_type VARCHAR(30) NOT NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cashier_point_id (point_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS repair_job_list (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_number VARCHAR(100) NOT NULL,
            customer_id BIGINT UNSIGNED NULL,
            item_name VARCHAR(200) NULL,
            imei_no VARCHAR(100) NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
            received_date DATETIME NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_repair_job_number (job_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS repair_falut_list (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            fault_name VARCHAR(150) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS repair_belongs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            belong_name VARCHAR(150) NOT NULL,
            valid TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS repair_map_belong (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_number VARCHAR(100) NOT NULL,
            belong_id BIGINT UNSIGNED NOT NULL,
            belong_val VARCHAR(255) NULL,
            recorded_time DATETIME NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_repair_map_job_number (job_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS repair_center_jobs_parts_add (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_number VARCHAR(100) NOT NULL,
            part_name VARCHAR(150) NOT NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_repair_parts_job_number (job_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS rapair_job_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_number VARCHAR(100) NOT NULL,
            log_status VARCHAR(100) NOT NULL,
            remark VARCHAR(255) NULL,
            op_time DATETIME NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_rapair_log_job_number (job_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS alter_bill_mainsale (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_no VARCHAR(100) NOT NULL,
            alt_date DATETIME NOT NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            discount DECIMAL(12,2) NOT NULL DEFAULT 0,
            net_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            recorder VARCHAR(100) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_alt_main_bill_no (bill_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS alter_bill_billdata (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_no VARCHAR(100) NOT NULL,
            item_id BIGINT UNSIGNED NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_alt_data_bill_no (bill_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS alter_bill_information (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_no VARCHAR(100) NOT NULL,
            reason VARCHAR(255) NULL,
            recorder VARCHAR(100) NULL,
            op_date DATETIME NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_alt_info_bill_no (bill_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS cancel_bill_mainsale (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_no VARCHAR(100) NOT NULL,
            cancel_date DATETIME NOT NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            recorder VARCHAR(100) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cancel_main_bill_no (bill_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS cancel_bill_billdetails (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_no VARCHAR(100) NOT NULL,
            item_id BIGINT UNSIGNED NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cancel_data_bill_no (bill_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS dash_data (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            data_key VARCHAR(100) NOT NULL,
            data_value VARCHAR(255) NULL,
            op_date DATETIME NOT NULL,
            INDEX idx_dash_data_key (data_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS log_sys_operation_1 (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(100) NULL,
            operation_name VARCHAR(150) NOT NULL,
            operation_data TEXT NULL,
            op_date DATETIME NOT NULL,
            INDEX idx_log_op_date (op_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS price_manual_edit (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id BIGINT UNSIGNED NULL,
            old_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            new_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            op_date DATETIME NOT NULL,
            recorder VARCHAR(100) NULL,
            INDEX idx_price_edit_item_id (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_alert (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id BIGINT UNSIGNED NULL,
            threshold_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            status TINYINT(1) NOT NULL DEFAULT 1,
            op_date DATETIME NULL,
            INDEX idx_stock_alert_item_id (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_transerror (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            transfer_no VARCHAR(100) NULL,
            error_note VARCHAR(255) NULL,
            op_date DATETIME NOT NULL,
            INDEX idx_transerror_transfer_no (transfer_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(\PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS shop_transerror');
        $db->exec('DROP TABLE IF EXISTS stock_alert');
        $db->exec('DROP TABLE IF EXISTS price_manual_edit');
        $db->exec('DROP TABLE IF EXISTS log_sys_operation_1');
        $db->exec('DROP TABLE IF EXISTS dash_data');
        $db->exec('DROP TABLE IF EXISTS cancel_bill_billdetails');
        $db->exec('DROP TABLE IF EXISTS cancel_bill_mainsale');
        $db->exec('DROP TABLE IF EXISTS alter_bill_information');
        $db->exec('DROP TABLE IF EXISTS alter_bill_billdata');
        $db->exec('DROP TABLE IF EXISTS alter_bill_mainsale');
        $db->exec('DROP TABLE IF EXISTS rapair_job_log');
        $db->exec('DROP TABLE IF EXISTS repair_center_jobs_parts_add');
        $db->exec('DROP TABLE IF EXISTS repair_map_belong');
        $db->exec('DROP TABLE IF EXISTS repair_belongs');
        $db->exec('DROP TABLE IF EXISTS repair_falut_list');
        $db->exec('DROP TABLE IF EXISTS repair_job_list');
        $db->exec('DROP TABLE IF EXISTS cashier_point_log');
        $db->exec('DROP TABLE IF EXISTS cashier_point_control');
        $db->exec('DROP TABLE IF EXISTS shop_rcv_operator');
        $db->exec('DROP TABLE IF EXISTS shop_rcv_cards');
        $db->exec('DROP TABLE IF EXISTS prod_item_color');
        $db->exec('DROP TABLE IF EXISTS sys_privilegemap');
        $db->exec('DROP TABLE IF EXISTS sys_privilege');
        $db->exec('DROP TABLE IF EXISTS sys_user');
        $db->exec('DROP TABLE IF EXISTS sys_company');
    }
};
