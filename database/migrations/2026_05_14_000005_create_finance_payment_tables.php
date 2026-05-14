<?php

declare(strict_types=1);

use Core\Migrations\Migration;

return new class extends Migration {
    public function up(\PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS account_supplier (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            supplier_id BIGINT UNSIGNED NULL,
            ref_no VARCHAR(100) NULL,
            dr DECIMAL(12,2) NOT NULL DEFAULT 0,
            cr DECIMAL(12,2) NOT NULL DEFAULT 0,
            balance DECIMAL(12,2) NOT NULL DEFAULT 0,
            op_date DATETIME NOT NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_account_supplier_id (supplier_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS account_customer (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id BIGINT UNSIGNED NULL,
            ref_no VARCHAR(100) NULL,
            dr DECIMAL(12,2) NOT NULL DEFAULT 0,
            cr DECIMAL(12,2) NOT NULL DEFAULT 0,
            balance DECIMAL(12,2) NOT NULL DEFAULT 0,
            op_date DATETIME NOT NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_account_customer_id (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS account_cheque (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ref_no VARCHAR(100) NULL,
            cheque_no VARCHAR(100) NOT NULL,
            bank_name VARCHAR(150) NULL,
            cheque_date DATE NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cheque_ref_no (ref_no),
            INDEX idx_cheque_no (cheque_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_bill_pay (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_no VARCHAR(100) NOT NULL,
            pay_date DATETIME NOT NULL,
            pay_type VARCHAR(40) NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            reference_no VARCHAR(100) NULL,
            recorder VARCHAR(100) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_bill_pay_bill_no (bill_no),
            INDEX idx_bill_pay_date (pay_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS shop_bill_pay_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_no VARCHAR(100) NOT NULL,
            pay_id BIGINT UNSIGNED NULL,
            old_due DECIMAL(12,2) NOT NULL DEFAULT 0,
            paid DECIMAL(12,2) NOT NULL DEFAULT 0,
            new_due DECIMAL(12,2) NOT NULL DEFAULT 0,
            op_date DATETIME NOT NULL,
            recorder VARCHAR(100) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_bill_log_bill_no (bill_no),
            INDEX idx_bill_log_pay_id (pay_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS account_cashcredit (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ref_no VARCHAR(100) NULL,
            cash_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            credit_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            op_date DATETIME NOT NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cashcredit_ref_no (ref_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS account_cashcredit_customer (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id BIGINT UNSIGNED NULL,
            ref_no VARCHAR(100) NULL,
            cash_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            credit_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            op_date DATETIME NOT NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cashcredit_customer_id (customer_id),
            INDEX idx_cashcredit_customer_ref (ref_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS cashin_account (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            acc_name VARCHAR(150) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            eff_date DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS cashin_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            acc_id BIGINT UNSIGNED NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            op_date DATETIME NOT NULL,
            recorder VARCHAR(100) NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cashin_acc_id (acc_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS expence_account (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            acc_name VARCHAR(150) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            eff_date DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS expence_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            acc_id BIGINT UNSIGNED NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            op_date DATETIME NOT NULL,
            recorder VARCHAR(100) NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_expence_acc_id (acc_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(\PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS expence_log');
        $db->exec('DROP TABLE IF EXISTS expence_account');
        $db->exec('DROP TABLE IF EXISTS cashin_log');
        $db->exec('DROP TABLE IF EXISTS cashin_account');
        $db->exec('DROP TABLE IF EXISTS account_cashcredit_customer');
        $db->exec('DROP TABLE IF EXISTS account_cashcredit');
        $db->exec('DROP TABLE IF EXISTS shop_bill_pay_log');
        $db->exec('DROP TABLE IF EXISTS shop_bill_pay');
        $db->exec('DROP TABLE IF EXISTS account_cheque');
        $db->exec('DROP TABLE IF EXISTS account_customer');
        $db->exec('DROP TABLE IF EXISTS account_supplier');
    }
};
