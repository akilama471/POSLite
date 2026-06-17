<?php

declare(strict_types=1);

class ReportEngine extends Model
{
    public function getShopSaleReport(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = "";
        $params = [
            "from" => $fromDate,
            "to" => $toDate
        ];

        if ($shopId > 0) {
            $shopCondition = "AND billed_shop = :shop_id";
            $params["shop_id"] = $shopId;
        } else {
            $shopCondition = "AND billed_shop >= 0";
        }

        $sql = "SELECT b.billnumber, b.billaddedtime, b.customer_name, b.totalbill,
                       s.visibledata as seller_name, o.visibledata as operator_name
                FROM shop_pos_billdetails b
                LEFT JOIN sys_user s ON b.seller_id = s.myid
                LEFT JOIN sys_user o ON b.operator = o.myid
                WHERE DATE(b.billaddedtime) >= :from 
                  AND DATE(b.billaddedtime) <= :to 
                  $shopCondition
                ORDER BY b.billaddedtime ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCategorySaleReport(string $fromDate, string $toDate, int $shopId, int $categoryId = -1): array
    {
        $shopCondition = "";
        $catCondition = "";
        $params = [
            "from" => $fromDate,
            "to" => $toDate
        ];

        if ($shopId > 0) {
            $shopCondition = "AND b.billed_shop = :shop_id";
            $params["shop_id"] = $shopId;
        } else {
            $shopCondition = "AND b.billed_shop >= 0";
        }

        if ($categoryId > 0) {
            $catCondition = "AND m.cat_id = :cat_id";
            $params["cat_id"] = $categoryId;
        } else {
            $catCondition = "AND m.cat_id >= 0";
        }

        $sql = "SELECT m.cat_id, c.catname, SUM(m.qty) as total_qty, SUM(m.sub_total) as total_value
                FROM shop_pos_mainsale m
                JOIN shop_pos_billdetails b ON m.billnumber = b.billnumber
                LEFT JOIN prod_category c ON m.cat_id = c.catid
                WHERE DATE(b.billaddedtime) >= :from 
                  AND DATE(b.billaddedtime) <= :to
                  $shopCondition
                  $catCondition
                GROUP BY m.cat_id
                ORDER BY total_qty DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCashierTransactions(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = "";
        $params = [
            "from" => $fromDate,
            "to" => $toDate
        ];

        if ($shopId > 0) {
            $shopCondition = "AND shop = :shop_id";
            $params["shop_id"] = $shopId;
        } else {
            $shopCondition = "AND shop >= 0";
        }

        $sql = "SELECT c.op_date, c.remark, c.cash_in, c.cash_out, u.visibledata as operator_name
                FROM cash_book c
                LEFT JOIN sys_user u ON c.user = u.myid
                WHERE DATE(c.op_date) >= :from 
                  AND DATE(c.op_date) <= :to 
                  AND c.open_balance = '0' 
                  AND c.close_balance = '0'
                  $shopCondition
                ORDER BY c.op_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getOpenCloseBalances(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = "";
        $params = [
            "from" => $fromDate,
            "to" => $toDate
        ];

        if ($shopId > 0) {
            $shopCondition = "AND c.shop = :shop_id";
            $params["shop_id"] = $shopId;
        } else {
            $shopCondition = "AND c.shop >= 0";
        }

        $sql = "SELECT c.op_date, c.remark, c.open_balance, c.close_balance, s.shop_info_name
                FROM cash_book c
                LEFT JOIN sys_shop s ON c.shop = s.shopid
                WHERE DATE(c.op_date) >= :from 
                  AND DATE(c.op_date) <= :to 
                  AND (c.open_balance > '0' OR c.close_balance > '0')
                  $shopCondition
                ORDER BY c.op_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getShopExpenses(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = "";
        $params = [
            "from" => $fromDate,
            "to" => $toDate
        ];

        if ($shopId > 0) {
            $shopCondition = "AND e.shop_id = :shop_id";
            $params["shop_id"] = $shopId;
        } else {
            $shopCondition = "AND e.shop_id >= 0";
        }

        $sql = "SELECT e.record_time, e.amount, e.remark, 
                       s.shop_info_name, a.acc_name, u.visibledata as operator_name
                FROM expence_log e
                LEFT JOIN sys_shop s ON e.shop_id = s.shopid
                LEFT JOIN expence_account a ON e.account_id = a.recordid
                LEFT JOIN sys_user u ON e.operator_id = u.myid
                WHERE DATE(e.record_time) >= :from 
                  AND DATE(e.record_time) <= :to
                  $shopCondition
                ORDER BY e.record_time ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getShopProfit(string $fromDate, string $toDate, int $shopId): array
    {
        $params = [
            "from" => $fromDate,
            "to" => $toDate
        ];

        if ($shopId > 0) {
            $shopConditionSale = "AND billed_shop = :shop_id";
            $shopConditionRep = "AND job_inshop = :shop_id";
            $shopConditionLoss = "AND shop_id = :shop_id";
            $params["shop_id"] = $shopId;
        } else {
            $shopConditionSale = "AND billed_shop >= 0";
            $shopConditionRep = "AND job_inshop >= 0";
            $shopConditionLoss = "AND shop_id >= 0";
        }

        $profit = [
            "all_sale_value" => 0.0,
            "all_sale_cost" => 0.0,
            "repair_bills" => 0.0,
            "repair_costs" => 0.0,
            "removed_stock_value" => 0.0,
            "net_profit" => 0.0
        ];

        // 1. All Sale Value & Cost
        $sqlSale = "SELECT SUM(sale_price * qty) as total_sale, SUM(cost * qty) as total_cost
                    FROM shop_pos_mainsale 
                    WHERE billnumber IN (
                        SELECT billnumber FROM shop_pos_billdetails 
                        WHERE DATE(billaddedtime) >= :from AND DATE(billaddedtime) <= :to $shopConditionSale
                    )";
        $stmtSale = $this->db->prepare($sqlSale);
        $stmtSale->execute($params);
        $resSale = $stmtSale->fetch();
        if ($resSale) {
            $profit['all_sale_value'] = (float) $resSale['total_sale'];
            $profit['all_sale_cost'] = (float) $resSale['total_cost'];
        }

        // 2. Repair Bills Value
        $sqlRepBills = "SELECT SUM(job_payment_adv + job_payment_total) as total_rep_bills
                        FROM repair_job_list 
                        WHERE job_status = '5' 
                          AND DATE(handover_time) >= :from AND DATE(handover_time) <= :to
                          $shopConditionRep";
        $stmtRepBills = $this->db->prepare($sqlRepBills);
        $stmtRepBills->execute($params);
        $resRepBills = $stmtRepBills->fetch();
        if ($resRepBills) {
            $profit['repair_bills'] = (float) $resRepBills['total_rep_bills'];
        }

        // 3. Repair Item Cost
        $sqlRepCost = "SELECT SUM(item_sell_price - item_cost_price) as total_rep_cost
                       FROM rapair_job_log 
                       WHERE job_number IN (
                           SELECT job_number FROM repair_job_list 
                           WHERE job_status = '5' AND DATE(handover_time) >= :from AND DATE(handover_time) <= :to
                       ) $shopConditionRep";
        $stmtRepCost = $this->db->prepare($sqlRepCost);
        $stmtRepCost->execute($params);
        $resRepCost = $stmtRepCost->fetch();
        if ($resRepCost) {
            $profit['repair_costs'] = (float) $resRepCost['total_rep_cost'];
        }

        // 4. Removed Stock (Stock Delete Log)
        $sqlLoss = "SELECT l.edit_row, i.item_cost_price, i.stock_current
                    FROM stock_delete_log l
                    LEFT JOIN shop_stock_item i ON l.edit_row = i.item_stock_id
                    WHERE l.type = '1' 
                      AND DATE(l.operation_time) >= :from AND DATE(l.operation_time) <= :to
                      $shopConditionLoss";
        $stmtLoss = $this->db->prepare($sqlLoss);
        $stmtLoss->execute($params);
        $resLoss = $stmtLoss->fetchAll();
        foreach ($resLoss as $loss) {
            if ($loss['item_cost_price'] && $loss['stock_current']) {
                $profit['removed_stock_value'] += ((float)$loss['item_cost_price'] * (float)$loss['stock_current']);
            }
        }

        // 5. Net Profit
        $profit['net_profit'] = ($profit['all_sale_value'] + $profit['repair_bills']) - 
                                ($profit['all_sale_cost'] + $profit['removed_stock_value'] + $profit['repair_costs']);

        return $profit;
    }

    public function getProductCategories(): array
    {
        $sql = "SELECT catid, catname, eff_date FROM prod_category ORDER BY catname ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getProductList(int $categoryId = 0): array
    {
        $catCondition = "";
        $params = [];
        if ($categoryId > 0) {
            $catCondition = "WHERE p.item_cat = :cat_id";
            $params["cat_id"] = $categoryId;
        }

        $sql = "SELECT p.item_id, p.item_name, p.used_type, c.catname 
                FROM prod_items p
                LEFT JOIN prod_category c ON p.item_cat = c.catid
                $catCondition
                ORDER BY p.item_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getComprehensiveStock(int $shopId, int $categoryId, string $availability): array
    {
        $shopConditionItem = $shopId > 0 ? "AND stock_in_shop = " . $shopId : "AND stock_in_shop >= 0";
        
        $catCondition = "";
        $params = [];
        if ($categoryId > 0) {
            $catCondition = "WHERE item_cat = :cat_id";
            $params["cat_id"] = $categoryId;
        }

        // Fetch products matching category
        $sqlProd = "SELECT item_id, item_name, used_type FROM prod_items $catCondition ORDER BY item_name ASC";
        $stmtProd = $this->db->prepare($sqlProd);
        $stmtProd->execute($params);
        $products = $stmtProd->fetchAll();

        $results = [];

        // Loop products and calculate stock (using efficient prepared statements)
        $stmtItem = $this->db->prepare("SELECT SUM(stock_current) as qty, SUM(stock_current * item_cost_price) as val FROM shop_stock_item WHERE stock_status = 1 AND item_name_id = :item_id $shopConditionItem");
        $stmtImei = $this->db->prepare("SELECT SUM(stock_current) as qty, SUM(stock_current * item_cost_price) as val FROM shop_stock_imei WHERE stock_status = 1 AND item_name_id = :item_id $shopConditionItem");
        $stmtRcv = $this->db->prepare("SELECT SUM(current_stock) as qty, SUM(current_stock * cost_price) as val FROM shop_rcv_stock WHERE stock_status = 1 AND item_name_id = :item_id $shopConditionItem");

        foreach ($products as $prod) {
            $type = (int)$prod['used_type'];
            $qty = 0;
            $val = 0;

            if ($type === 1) { // barcode
                $stmtItem->execute(['item_id' => $prod['item_id']]);
                $res = $stmtItem->fetch();
                $qty = (float)($res['qty'] ?? 0);
                $val = (float)($res['val'] ?? 0);
            } else if ($type === 2) { // IMEI
                $stmtImei->execute(['item_id' => $prod['item_id']]);
                $res = $stmtImei->fetch();
                $qty = (float)($res['qty'] ?? 0);
                $val = (float)($res['val'] ?? 0);
            } else if ($type === 3) { // Recharge
                $stmtRcv->execute(['item_id' => $prod['item_id']]);
                $res = $stmtRcv->fetch();
                $qty = (float)($res['qty'] ?? 0);
                $val = (float)($res['val'] ?? 0);
            }

            // Filter by availability
            $keep = false;
            if ($availability === 'all') {
                $keep = true;
            } else if ($availability === 'in_stock' && $qty > 0) {
                $keep = true;
            } else if ($availability === 'empty' && $qty <= 0) {
                $keep = true;
            }

            if ($keep) {
                $results[] = [
                    'item_id' => $prod['item_id'],
                    'item_name' => $prod['item_name'],
                    'stock_count' => $qty,
                    'stock_value' => $val
                ];
            }
        }

        return $results;
    }

    // ── Supplier ──────────────────────────────────────────────

    public function getSupplierMaster(): array
    {
        $sql = "SELECT supplierid, supplier_name, supplier_address, supplier_mobile, eff_date,
                       cash_credit_balance, accbalance
                FROM shop_supplier ORDER BY supplier_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSupplierLedger(int $supplierId, string $fromDate, string $toDate): array
    {
        $sql = "SELECT a.recordtime, a.op_type, a.details, a.debit, a.credit,
                       s.supplier_name
                FROM account_supplier a
                LEFT JOIN shop_supplier s ON a.supplier = s.supplierid
                WHERE a.supplier = :supplier_id
                  AND DATE(a.recordtime) >= :from
                  AND DATE(a.recordtime) <= :to
                ORDER BY a.recordtime ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['supplier_id' => $supplierId, 'from' => $fromDate, 'to' => $toDate]);
        return $stmt->fetchAll();
    }

    public function getSupplierList(): array
    {
        $sql = "SELECT supplierid, supplier_name FROM shop_supplier ORDER BY supplier_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ── Customer ──────────────────────────────────────────────

    public function getCustomerMaster(): array
    {
        $sql = "SELECT recordid, cus_name, cus_addr, cus_mobile, add_time, accbalance
                FROM shop_customer WHERE recordid > 0 ORDER BY cus_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCustomerLedger(int $customerId, string $fromDate, string $toDate): array
    {
        $sql = "SELECT a.recordtime, a.details, a.debit, a.credit,
                       c.cus_name
                FROM account_customer a
                LEFT JOIN shop_customer c ON a.customer = c.recordid
                WHERE a.customer = :customer_id
                  AND DATE(a.recordtime) >= :from
                  AND DATE(a.recordtime) <= :to
                ORDER BY a.recordtime ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['customer_id' => $customerId, 'from' => $fromDate, 'to' => $toDate]);
        return $stmt->fetchAll();
    }

    public function getCustomerList(): array
    {
        $sql = "SELECT recordid, cus_name FROM shop_customer WHERE recordid > 0 ORDER BY cus_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ── User / Security ───────────────────────────────────────

    public function getUserMaster(): array
    {
        $sql = "SELECT u.myid, u.ankaya, u.lastlogin, u.statusu,
                       s.shop_info_name, p.privilegename
                FROM sys_user u
                LEFT JOIN sys_shop s ON u.shop_id = s.shopid
                LEFT JOIN sys_privilege p ON u.privilageid = p.privilegeid
                ORDER BY u.visibledata ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUserSales(int $shopId, string $fromDate, string $toDate): array
    {
        $shopCondition = $shopId > 0 ? "AND u.shop_id = :shop_id" : "AND u.shop_id >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT u.myid, u.visibledata as user_name,
                       COUNT(b.recordid) as sale_count,
                       COALESCE(SUM(m.sale_profit), 0) as sale_profit
                FROM sys_user u
                LEFT JOIN shop_pos_billdetails b 
                    ON b.seller_id = u.myid
                   AND DATE(b.billaddedtime) >= :from AND DATE(b.billaddedtime) <= :to
                LEFT JOIN (
                    SELECT billnumber, SUM((sale_price - cost) * qty) as sale_profit
                    FROM shop_pos_mainsale GROUP BY billnumber
                ) m ON m.billnumber = b.billnumber
                WHERE 1=1 $shopCondition
                GROUP BY u.myid
                ORDER BY sale_count DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getSecurityLog(string $fromDate, string $toDate): array
    {
        $sql = "SELECT l.opedate, l.operation, l.useip,
                       u.visibledata as user_name
                FROM log_sys_operation_1 l
                LEFT JOIN sys_user u ON l.userid = u.myid
                WHERE DATE(l.opedate) >= :from AND DATE(l.opedate) <= :to
                ORDER BY l.opedate DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['from' => $fromDate, 'to' => $toDate]);
        return $stmt->fetchAll();
    }

    // ── GRN Reports ───────────────────────────────────────────

    public function getGrnList(int $shopId, string $fromDate, string $toDate): array
    {
        $shopCond = $shopId > 0 ? "AND g.shop_number = :shop_id" : "AND g.shop_number >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT g.grn_refno, g.suppler_name, g.operation_time,
                       g.final_amount, g.cash_amount, g.chq_amount,
                       s.shop_info_name
                FROM shop_grnmain g
                LEFT JOIN sys_shop s ON g.shop_number = s.shopid
                WHERE DATE(g.operation_time) >= :from
                  AND DATE(g.operation_time) <= :to
                  $shopCond
                ORDER BY g.operation_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getGrnDetail(string $grnRefNo): array
    {
        $sqlMain = "SELECT g.*, s.shop_info_name
                    FROM shop_grnmain g
                    LEFT JOIN sys_shop s ON g.shop_number = s.shopid
                    WHERE g.grn_refno = :grn_refno LIMIT 1";
        $stmtMain = $this->db->prepare($sqlMain);
        $stmtMain->execute(['grn_refno' => $grnRefNo]);
        $header = $stmtMain->fetch();

        $sqlItems = "SELECT i.*, sh.shop_info_name as stock_shop_name
                     FROM shop_grnitem i
                     LEFT JOIN sys_shop sh ON i.stock_shop = sh.shopid
                     WHERE i.grn_refno = :grn_refno";
        $stmtItems = $this->db->prepare($sqlItems);
        $stmtItems->execute(['grn_refno' => $grnRefNo]);
        $items = $stmtItems->fetchAll();

        return ['header' => $header, 'items' => $items];
    }

    public function getGrnReorder(int $shopId, int $categoryId): array
    {
        $shopCond = $shopId > 0 ? "AND sa.shop_id = :shop_id" : "AND sa.shop_id >= 0";
        $catCond  = $categoryId > 0 ? "AND sa.cat_id = :cat_id" : "";
        $params = [];
        if ($shopId > 0) $params['shop_id'] = $shopId;
        if ($categoryId > 0) $params['cat_id'] = $categoryId;

        $sql = "SELECT sa.item_id, sa.item_name, sa.alert_qty, sa.current_qty,
                       s.shop_info_name
                FROM stock_alert sa
                LEFT JOIN sys_shop s ON sa.shop_id = s.shopid
                WHERE sa.exp_time >= NOW()
                  AND sa.alert_qty >= sa.current_qty
                  $shopCond $catCond
                ORDER BY sa.current_qty ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Inventory Logs ────────────────────────────────────────

    public function getPriceEditLog(string $fromDate, string $toDate): array
    {
        $sql = "SELECT l.row_id, l.item_name, l.reason, l.old_price, l.new_price,
                       l.operation_time,
                       s.shop_info_name, u.visibledata as operator_name
                FROM price_manual_edit l
                LEFT JOIN sys_shop s ON l.change_shop = s.shopid
                LEFT JOIN sys_user u ON l.change_user = u.myid
                WHERE DATE(l.operation_time) >= :from AND DATE(l.operation_time) <= :to
                ORDER BY l.operation_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['from' => $fromDate, 'to' => $toDate]);
        return $stmt->fetchAll();
    }

    public function getStockEditLog(string $fromDate, string $toDate): array
    {
        $sql = "SELECT l.sys_remark, l.reason, l.operation_time,
                       s.shop_info_name, u.visibledata as operator_name
                FROM stock_edit_log l
                LEFT JOIN sys_shop s ON l.shop_id = s.shopid
                LEFT JOIN sys_user u ON l.operator = u.myid
                WHERE DATE(l.operation_time) >= :from AND DATE(l.operation_time) <= :to
                ORDER BY l.operation_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['from' => $fromDate, 'to' => $toDate]);
        return $stmt->fetchAll();
    }

    public function getStockDeleteLog(string $fromDate, string $toDate): array
    {
        $sql = "SELECT l.sys_remark, l.reason, l.operation_time,
                       s.shop_info_name, u.visibledata as operator_name
                FROM stock_delete_log l
                LEFT JOIN sys_shop s ON l.shop_id = s.shopid
                LEFT JOIN sys_user u ON l.operator = u.myid
                WHERE DATE(l.operation_time) >= :from AND DATE(l.operation_time) <= :to
                ORDER BY l.operation_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['from' => $fromDate, 'to' => $toDate]);
        return $stmt->fetchAll();
    }

    // ── Repair Reports ────────────────────────────────────────

    public function getRepairJobs(int $shopId, string $fromDate, string $toDate, ?int $statusFilter = null): array
    {
        $shopCond = $shopId > 0 ? "AND j.job_inshop = :shop_id" : "AND j.job_inshop >= 0";
        $statusCond = "";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;
        if ($statusFilter !== null) {
            $statusCond = "AND j.job_status = :status";
            $params['status'] = $statusFilter;
        }

        $sql = "SELECT j.job_number, j.job_cus_name, j.job_cus_contac, j.job_cus_imei,
                       j.job_fault, j.job_add_date, j.job_status,
                       j.bill_make_time, j.handover_time,
                       s.shop_info_name
                FROM repair_job_list j
                LEFT JOIN sys_shop s ON j.job_inshop = s.shopid
                WHERE DATE(j.job_add_date) >= :from
                  AND DATE(j.job_add_date) <= :to
                  $shopCond $statusCond
                ORDER BY j.job_add_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRepairJobDetail(string $jobNumber): array
    {
        $sqlJob = "SELECT j.*, s.shop_info_name
                   FROM repair_job_list j
                   LEFT JOIN sys_shop s ON j.job_inshop = s.shopid
                   WHERE j.job_number = :job_number LIMIT 1";
        $stmtJob = $this->db->prepare($sqlJob);
        $stmtJob->execute(['job_number' => $jobNumber]);
        $header = $stmtJob->fetch();

        $sqlLog = "SELECT l.a_item_name, l.a_item_gen_refno, l.warranty_span, l.warranty_type,
                          l.item_sell_price, l.item_cost_price, l.record_time,
                          u.visibledata as operator_name
                   FROM rapair_job_log l
                   LEFT JOIN sys_user u ON l.user_id = u.myid
                   WHERE l.job_number = :job_number";
        $stmtLog = $this->db->prepare($sqlLog);
        $stmtLog->execute(['job_number' => $jobNumber]);
        $log = $stmtLog->fetchAll();

        return ['header' => $header, 'log' => $log];
    }

    // ── Stock Transfer Reports ────────────────────────────────

    public function getTransferList(int $shopId, string $fromDate, string $toDate): array
    {
        $shopCond = $shopId > 0 ? "AND t.trans_fromshop = :shop_id" : "AND t.trans_fromshop >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT t.trans_id, t.record_time, t.total_cost, t.trans_status,
                       s.shop_info_name as from_shop_name,
                       u.visibledata as operator_name
                FROM stock_transmain t
                LEFT JOIN sys_shop s ON t.trans_fromshop = s.shopid
                LEFT JOIN sys_user u ON t.processed_operator = u.myid
                WHERE DATE(t.record_time) >= :from
                  AND DATE(t.record_time) <= :to
                  $shopCond
                ORDER BY t.record_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTransferDetail(string $transId): array
    {
        $sqlMain = "SELECT t.*, s.shop_info_name as from_shop_name, u.visibledata as operator_name
                    FROM stock_transmain t
                    LEFT JOIN sys_shop s ON t.trans_fromshop = s.shopid
                    LEFT JOIN sys_user u ON t.processed_operator = u.myid
                    WHERE t.trans_id = :trans_id LIMIT 1";
        $stmtMain = $this->db->prepare($sqlMain);
        $stmtMain->execute(['trans_id' => $transId]);
        $header = $stmtMain->fetch();

        $sqlItems = "SELECT l.Item_name, l.code, l.stock_count, l.part_cost, l.transfer_value,
                            s.shop_info_name as to_shop_name
                     FROM stock_translog l
                     LEFT JOIN sys_shop s ON l.to_shop = s.shopid
                     WHERE l.trans_id = :trans_id ORDER BY l.recorded_time ASC";
        $stmtItems = $this->db->prepare($sqlItems);
        $stmtItems->execute(['trans_id' => $transId]);
        $items = $stmtItems->fetchAll();

        return ['header' => $header, 'items' => $items];
    }

    public function getTransferLogCheck(string $itemCode): array
    {
        $sql = "SELECT l.trans_id, l.Item_name, l.code, l.stock_count, l.recorded_time,
                       sf.shop_info_name as from_shop_name,
                       st.shop_info_name as to_shop_name
                FROM stock_translog l
                LEFT JOIN sys_shop sf ON l.from_shop = sf.shopid
                LEFT JOIN sys_shop st ON l.to_shop = st.shopid
                WHERE l.code = :code
                ORDER BY l.recorded_time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['code' => $itemCode]);
        return $stmt->fetchAll();
    }

    // ── Extended Sales Reports ─────────────────────────────────

    public function getBestSaleReport(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = $shopId > 0 ? "AND b.billed_shop = :shop_id" : "AND b.billed_shop >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT m.item_name, m.imei_part_no, SUM(m.qty) as total_qty, SUM(m.sub_total) as total_value
                FROM shop_pos_mainsale m
                JOIN shop_pos_billdetails b ON m.billnumber = b.billnumber
                WHERE DATE(b.billaddedtime) >= :from 
                  AND DATE(b.billaddedtime) <= :to
                  $shopCondition
                GROUP BY m.item_name, m.imei_part_no
                ORDER BY total_qty DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getItemWiseSale(string $fromDate, string $toDate, int $shopId, string $itemName): array
    {
        $shopCondition = $shopId > 0 ? "AND b.billed_shop = :shop_id" : "AND b.billed_shop >= 0";
        $itemCondition = $itemName !== '' ? "AND m.item_name LIKE :item_name" : "";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;
        if ($itemName !== '') $params['item_name'] = "%" . $itemName . "%";

        $sql = "SELECT b.billnumber, b.billaddedtime as sale_date, m.item_name, m.imei_part_no, m.qty, m.sale_price, m.sub_total
                FROM shop_pos_mainsale m
                JOIN shop_pos_billdetails b ON m.billnumber = b.billnumber
                WHERE DATE(b.billaddedtime) >= :from 
                  AND DATE(b.billaddedtime) <= :to
                  $shopCondition $itemCondition
                ORDER BY b.billaddedtime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getItemCatWiseSale(string $fromDate, string $toDate, int $shopId, int $categoryId): array
    {
        $shopCondition = $shopId > 0 ? "AND b.billed_shop = :shop_id" : "AND b.billed_shop >= 0";
        $catCondition = $categoryId > 0 ? "AND m.cat_id = :cat_id" : "";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;
        if ($categoryId > 0) $params['cat_id'] = $categoryId;

        $sql = "SELECT c.catname, m.item_name, SUM(m.qty) as total_qty, SUM(m.sub_total) as total_value
                FROM shop_pos_mainsale m
                JOIN shop_pos_billdetails b ON m.billnumber = b.billnumber
                LEFT JOIN prod_category c ON m.cat_id = c.catid
                WHERE DATE(b.billaddedtime) >= :from 
                  AND DATE(b.billaddedtime) <= :to
                  $shopCondition $catCondition
                GROUP BY m.cat_id, m.item_name
                ORDER BY c.catname ASC, total_qty DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getOverCostSale(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = $shopId > 0 ? "AND b.billed_shop = :shop_id" : "AND b.billed_shop >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT b.billnumber, b.billaddedtime as sale_date, m.item_name, m.cost as cost_price, m.sale_price
                FROM shop_pos_mainsale m
                JOIN shop_pos_billdetails b ON m.billnumber = b.billnumber
                WHERE DATE(b.billaddedtime) >= :from 
                  AND DATE(b.billaddedtime) <= :to
                  AND m.sale_price > m.cost
                  $shopCondition
                ORDER BY b.billaddedtime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getUnderCostSale(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = $shopId > 0 ? "AND b.billed_shop = :shop_id" : "AND b.billed_shop >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT b.billnumber, b.billaddedtime as sale_date, m.item_name, m.cost as cost_price, m.sale_price
                FROM shop_pos_mainsale m
                JOIN shop_pos_billdetails b ON m.billnumber = b.billnumber
                WHERE DATE(b.billaddedtime) >= :from 
                  AND DATE(b.billaddedtime) <= :to
                  AND m.sale_price < m.cost
                  $shopCondition
                ORDER BY b.billaddedtime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getPhoneSale(string $fromDate, string $toDate, int $shopId, string $imei): array
    {
        $shopCondition = $shopId > 0 ? "AND b.billed_shop = :shop_id" : "AND b.billed_shop >= 0";
        $imeiCondition = $imei !== '' ? "AND m.imei_part_no LIKE :imei" : "";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;
        if ($imei !== '') $params['imei'] = "%" . $imei . "%";

        $sql = "SELECT b.billnumber, b.billaddedtime as sale_date, b.customer_name as cus_name, m.item_name, m.imei_part_no, m.sale_price
                FROM shop_pos_mainsale m
                JOIN shop_pos_billdetails b ON m.billnumber = b.billnumber
                WHERE DATE(b.billaddedtime) >= :from 
                  AND DATE(b.billaddedtime) <= :to
                  AND (m.imei_part_no IS NOT NULL AND m.imei_part_no != '')
                  $shopCondition $imeiCondition
                ORDER BY b.billaddedtime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Extended Cashier Reports ───────────────────────────────

    public function getCashierCashIn(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = $shopId > 0 ? "AND c.shop_id = :shop_id" : "AND c.shop_id >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT c.record_time, a.acc_name, u.visibledata as operator_name, c.remark, c.amount
                FROM cashin_log c
                LEFT JOIN cashin_account a ON c.account_id = a.recordid
                LEFT JOIN sys_user u ON c.operator_id = u.myid
                WHERE DATE(c.record_time) >= :from 
                  AND DATE(c.record_time) <= :to
                  $shopCondition
                ORDER BY c.record_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCashierAccWiseExpenses(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = $shopId > 0 ? "AND e.shop_id = :shop_id" : "AND e.shop_id >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT a.acc_name, COUNT(e.recordid) as count, SUM(e.amount) as total_amount
                FROM expence_log e
                LEFT JOIN expence_account a ON e.account_id = a.recordid
                WHERE DATE(e.record_time) >= :from 
                  AND DATE(e.record_time) <= :to
                  $shopCondition
                GROUP BY e.account_id
                ORDER BY total_amount DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCashierOperationLog(string $fromDate, string $toDate, int $shopId): array
    {
        // No strict shop relation in cashier_point_log, but we can filter by user's shop if needed. Assuming it matches legacy.
        // Actually legacy checks user's shop. For simplicity, just time filter.
        $params = ['from' => $fromDate, 'to' => $toDate];

        $sql = "SELECT c.operation_slot, u.visibledata as operator_name, c.recordtime, c.close_time, 
                       c.cash_openbal, c.cash_closebal, c.card_openbal, c.card_closebal
                FROM cashier_point_log c
                LEFT JOIN sys_user u ON c.user_id = u.myid
                WHERE DATE(c.recordtime) >= :from 
                  AND DATE(c.recordtime) <= :to
                ORDER BY c.recordtime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Extended GRN Reports ───────────────────────────────────

    public function getGrnReturnList(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = $shopId > 0 ? "AND r.shop_id = :shop_id" : "AND r.shop_id >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT r.return_refno, r.return_date, r.grn_refno, s.supplier_name as suppler_name, 
                       r.item_count, r.total_value, 
                       CASE r.status WHEN 1 THEN 'Returned' WHEN 0 THEN 'Pending' ELSE 'Canceled' END as status_label
                FROM stock_return_main r
                LEFT JOIN shop_supplier s ON r.supplier_id = s.supplierid
                WHERE DATE(r.return_date) >= :from 
                  AND DATE(r.return_date) <= :to
                  $shopCondition
                ORDER BY r.return_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getGrnReturnDetail(string $returnRef): array
    {
        $sql = "SELECT item_name, imei_no, item_qty, item_cost, return_value
                FROM stock_return_log
                WHERE return_refno = :return_ref";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['return_ref' => $returnRef]);
        return $stmt->fetchAll();
    }

    public function getGrnDiscardLog(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = $shopId > 0 ? "AND d.shop_id = :shop_id" : "AND d.shop_id >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT d.discard_date, d.item_name, d.imei_no, u.visibledata as operator_name, 
                       d.discard_reason, d.qty, d.item_cost
                FROM stock_discard_log d
                LEFT JOIN sys_user u ON d.user_id = u.myid
                WHERE DATE(d.discard_date) >= :from 
                  AND DATE(d.discard_date) <= :to
                  $shopCondition
                ORDER BY d.discard_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getGrnTransferBin(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = $shopId > 0 ? "AND b.shop_id = :shop_id" : "AND b.shop_id >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT b.record_date, b.item_name, b.imei_no, s.supplier_name as suppler_name, 
                       b.qty, b.item_cost, 
                       CASE b.status WHEN 1 THEN 'Processed' ELSE 'Pending' END as status_label
                FROM stock_transfer_bin b
                LEFT JOIN shop_supplier s ON b.supplier_id = s.supplierid
                WHERE DATE(b.record_date) >= :from 
                  AND DATE(b.record_date) <= :to
                  $shopCondition
                ORDER BY b.record_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getGrnSalesReturnBin(string $fromDate, string $toDate, int $shopId): array
    {
        $shopCondition = $shopId > 0 ? "AND r.shop_id = :shop_id" : "AND r.shop_id >= 0";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;

        $sql = "SELECT r.return_date, r.billnumber, c.cus_name, r.item_name, r.imei_part_no, 
                       r.return_qty, r.return_value
                FROM shop_sales_return r
                LEFT JOIN shop_pos_billdetails b ON r.billnumber = b.billnumber
                LEFT JOIN shop_customer c ON b.customer_id = c.recordid
                WHERE DATE(r.return_date) >= :from 
                  AND DATE(r.return_date) <= :to
                  $shopCondition
                ORDER BY r.return_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getGrnSupplierWise(string $fromDate, string $toDate, int $shopId, int $supplierId): array
    {
        $shopCondition = $shopId > 0 ? "AND g.shop_number = :shop_id" : "AND g.shop_number >= 0";
        $supCondition = $supplierId > 0 ? "AND g.supplier_id = :supplier_id" : "";
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($shopId > 0) $params['shop_id'] = $shopId;
        if ($supplierId > 0) $params['supplier_id'] = $supplierId;

        $sql = "SELECT s.supplier_name as suppler_name, COUNT(g.grn_refno) as grn_count, 
                       SUM(g.final_amount) as total_value, SUM(g.cash_amount + g.chq_amount) as total_paid
                FROM shop_grnmain g
                LEFT JOIN shop_supplier s ON g.supplier_id = s.supplierid
                WHERE DATE(g.operation_time) >= :from 
                  AND DATE(g.operation_time) <= :to
                  $shopCondition $supCondition
                GROUP BY g.supplier_id
                ORDER BY total_value DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}