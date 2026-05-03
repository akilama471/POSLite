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
}
