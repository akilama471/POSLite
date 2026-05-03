<?php

declare(strict_types=1);

class RepairJob extends Model
{
    protected $table = 'repair_job_list';

    public function generateJobNumber(int $shopId, int $userId): array
    {
        $ym = date('Ym');
        $shortYm = substr($ym, 2);

        $stmt = $this->db->prepare("SELECT MAX(job_sequ) as max_seq FROM repair_job_list WHERE job_month = :ym");
        $stmt->execute(['ym' => $ym]);
        $row = $stmt->fetch();
        $nextSeq = (int) ($row['max_seq'] ?? 0) + 1;

        $padSeq = str_pad((string) $nextSeq, 4, "0", STR_PAD_LEFT);
        $jobNumber = "R" . $shortYm . $padSeq . $userId;

        return [
            "job_number" => $jobNumber,
            "job_month" => $ym,
            "job_sequ" => $nextSeq
        ];
    }

    public function createJob(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO repair_job_list (
                job_number, job_month, job_sequ, job_inshop, job_operator,
                job_cus_name, job_cus_imei, job_cus_model, job_cus_addr,
                job_cus_contac, job_fault, job_status, job_payment_adv,
                job_payment_total, rp_for_warranty, job_add_date
            ) VALUES (
                :job_number, :job_month, :job_sequ, :job_inshop, :job_operator,
                :job_cus_name, :job_cus_imei, :job_cus_model, :job_cus_addr,
                :job_cus_contac, :job_fault, :job_status, :job_payment_adv,
                0, :rp_for_warranty, :now
            )"
        );

        $stmt->execute([
            "job_number" => $data["job_number"],
            "job_month" => $data["job_month"],
            "job_sequ" => $data["job_sequ"],
            "job_inshop" => $data["job_inshop"],
            "job_operator" => $data["job_operator"],
            "job_cus_name" => $data["job_cus_name"],
            "job_cus_imei" => $data["job_cus_imei"],
            "job_cus_model" => $data["job_cus_model"],
            "job_cus_addr" => $data["job_cus_addr"],
            "job_cus_contac" => $data["job_cus_contac"],
            "job_fault" => $data["job_fault"],
            "job_status" => 1,
            "job_payment_adv" => $data["job_payment_adv"],
            "rp_for_warranty" => $data["rp_for_warranty"],
            "now" => date('Y-m-d H:i:s'),
        ]);
    }

    public function findByJobNumber(string $jobNumber): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM repair_job_list WHERE job_number = :job");
        $stmt->execute(["job" => $jobNumber]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getJobsByStatus(array $statuses, int $shopId): array
    {
        $in = str_repeat('?,', count($statuses) - 1) . '?';
        $params = $statuses;
        $shopCondition = "";

        if ($shopId > 0) {
            $shopCondition = "AND job_inshop = ?";
            $params[] = $shopId;
        }

        $stmt = $this->db->prepare("SELECT * FROM repair_job_list WHERE job_status IN ($in) $shopCondition ORDER BY recordid DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function finishTechnical(string $jobNumber): void
    {
        $stmt = $this->db->prepare("UPDATE repair_job_list SET job_status = 3, job_comp_date = :now WHERE job_number = :job");
        $stmt->execute([
            "job" => $jobNumber,
            "now" => date('Y-m-d H:i:s'),
        ]);
    }

    public function makeBill(string $jobNumber, float $partCost, float $repairCost, float $total, string $warrantySpan, string $warrantyType, int $printWarranty): void
    {
        $warranty = trim($warrantySpan . " " . $warrantyType);
        $stmt = $this->db->prepare(
            "UPDATE repair_job_list
             SET job_status = 4, bill_make_time = :now,
                 job_payment_total = :total, job_partcost = :partcost,
                 job_repaircost = :repcost, job_warranty = :warranty,
                 job_warranty_print = :print_war
             WHERE job_number = :job"
        );
        $stmt->execute([
            "now" => date('Y-m-d H:i:s'),
            "total" => $total,
            "partcost" => $partCost,
            "repcost" => $repairCost,
            "warranty" => $warranty,
            "print_war" => $printWarranty,
            "job" => $jobNumber,
        ]);
    }

    public function handover(string $jobNumber, float $cash, float $card, string $cardNumber, float $balance, float $totalGiven): void
    {
        $stmt = $this->db->prepare(
            "UPDATE repair_job_list
             SET job_status = 5, handover_time = :now,
                 amt_cus_give = :give, cus_cash_pay = :cash,
                 cus_card_pay = :card, card_number = :cnum,
                 amt_cus_balance = :bal
             WHERE job_number = :job"
        );
        $stmt->execute([
            "now" => date('Y-m-d H:i:s'),
            "give" => $totalGiven,
            "cash" => $cash,
            "card" => $card,
            "cnum" => $cardNumber,
            "bal" => $balance,
            "job" => $jobNumber,
        ]);
    }

    public function addPart(string $jobNumber, string $partRef, int $userId, int $shopId): void
    {
        $this->db->beginTransaction();

        try {
            // Get item info and decrement stock
            $stmt1 = $this->db->prepare(
                "SELECT * FROM shop_stock_item
                 WHERE gen_refno = :ref AND stock_current > 0 AND stock_status = 1 AND stock_in_shop = :shop LIMIT 1"
            );
            $stmt1->execute(["ref" => $partRef, "shop" => $shopId]);
            $item = $stmt1->fetch();

            if (!$item) {
                throw new Exception("Item out of stock or not found for this barcode.");
            }

            $stmt2 = $this->db->prepare(
                "UPDATE shop_stock_item SET stock_current = stock_current - 1
                 WHERE item_stock_id = :id"
            );
            $stmt2->execute(["id" => $item['item_stock_id']]);

            // Add to repair_job_log
            $logModel = new RepairLog();
            $logModel->db = $this->db;
            $logModel->addLog(
                $jobNumber,
                "2",
                $item['item_name'],
                $item['gen_refno'],
                (float) $item['item_cost_price'],
                (float) $item['item_sell_price'],
                $userId
            );

            // Add to repair_center_jobs_parts_add
            $stmt3 = $this->db->prepare(
                "INSERT INTO repair_center_jobs_parts_add
                 (_job_number, _item_refno, _item_cat_id, _item_id, _item_price, _item_qty, _user_id, _shop_id, _date, _item_supplier)
                 VALUES
                 (:job, :ref, :cat, :itemid, :price, 1, :user, :shop, :now, :sup)"
            );
            $stmt3->execute([
                "job" => $jobNumber,
                "ref" => $item['gen_refno'],
                "cat" => $item['item_cat_id'],
                "itemid" => $item['item_name_id'],
                "price" => $item['item_sell_price'],
                "user" => $userId,
                "shop" => $shopId,
                "now" => date('Y-m-d H:i:s'),
                "sup" => $item['supplier_id'],
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
