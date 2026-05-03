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
}
