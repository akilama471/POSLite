<?php

declare(strict_types=1);

class StockTransfer extends Model
{
    protected $table = "stock_transmain";

    public function draft(array $auth): array
    {
        return $_SESSION[$this->draftSessionKey($auth)] ?? [
            "target_shop_id" => 0,
            "target_shop_name" => "",
            "lines" => [],
        ];
    }

    public function setDraftTarget(array $auth, int $targetShopId): void
    {
        $shopId = (int) ($auth["shop_id"] ?? 0);
        if ($shopId > 0 && $shopId === $targetShopId) {
            throw new RuntimeException("Transfer target shop must be different from the current shop.");
        }

        $shopModel = new Shop();
        $shop = $shopModel->findByShopId($targetShopId);
        if ($shop === null) {
            throw new RuntimeException("Target shop was not found.");
        }

        $draft = $this->draft($auth);
        $draft["target_shop_id"] = $targetShopId;
        $draft["target_shop_name"] = (string) ($shop["shop_info_name"] ?? $shop["shopname"] ?? "");
        $_SESSION[$this->draftSessionKey($auth)] = $draft;
    }

    public function clearDraft(array $auth): void
    {
        unset($_SESSION[$this->draftSessionKey($auth)]);
    }

    public function findCandidatesByCode(string $code, int $shopId): array
    {
        $code = trim($code);
        if ($code === "") {
            return [];
        }

        $rows = [];

        $barcodeStmt = $this->db->prepare(
            "SELECT 1 AS object_type,
                    stock.item_stock_id AS row_id,
                    stock.item_name,
                    stock.gen_refno AS item_code,
                    stock.stock_current AS stock_current,
                    stock.item_cost_price AS part_cost
             FROM shop_stock_item AS stock
             WHERE stock.gen_refno = :code
               AND stock.stock_status = 1
               AND stock.stock_current > 0
               AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)",
        );
        $barcodeStmt->execute([
            "code" => $code,
            "shop_id" => $shopId,
        ]);
        foreach ($barcodeStmt->fetchAll() as $row) {
            $rows[] = $this->normalizeCandidateRow($row);
        }

        $imeiStmt = $this->db->prepare(
            "SELECT 2 AS object_type,
                    stock.item_stock_id_imei AS row_id,
                    stock.item_name,
                    stock.imei_no AS item_code,
                    stock.stock_current AS stock_current,
                    stock.item_cost_price AS part_cost
             FROM shop_stock_imei AS stock
             WHERE stock.imei_no = :code
               AND stock.stock_status = 1
               AND stock.stock_current > 0
               AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)",
        );
        $imeiStmt->execute([
            "code" => $code,
            "shop_id" => $shopId,
        ]);
        foreach ($imeiStmt->fetchAll() as $row) {
            $rows[] = $this->normalizeCandidateRow($row);
        }

        return $rows;
    }

    public function findCandidatesByItemName(string $itemName, int $shopId): array
    {
        $itemName = trim($itemName);
        if ($itemName === "") {
            return [];
        }

        $itemModel = new Item();
        $item = $itemModel->findByName($itemName);
        if ($item === null) {
            return [];
        }

        $itemId = (int) ($item["item_id"] ?? 0);
        $rows = [];

        $barcodeStmt = $this->db->prepare(
            "SELECT 1 AS object_type,
                    stock.item_stock_id AS row_id,
                    stock.item_name,
                    stock.gen_refno AS item_code,
                    stock.stock_current AS stock_current,
                    stock.item_cost_price AS part_cost
             FROM shop_stock_item AS stock
             WHERE stock.item_name_id = :item_id
               AND stock.stock_status = 1
               AND stock.stock_current > 0
               AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
             ORDER BY stock.item_stock_id ASC",
        );
        $barcodeStmt->execute([
            "item_id" => $itemId,
            "shop_id" => $shopId,
        ]);
        foreach ($barcodeStmt->fetchAll() as $row) {
            $rows[] = $this->normalizeCandidateRow($row);
        }

        $imeiStmt = $this->db->prepare(
            "SELECT 2 AS object_type,
                    stock.item_stock_id_imei AS row_id,
                    stock.item_name,
                    stock.imei_no AS item_code,
                    stock.stock_current AS stock_current,
                    stock.item_cost_price AS part_cost
             FROM shop_stock_imei AS stock
             WHERE stock.item_name_id = :item_id
               AND stock.stock_status = 1
               AND stock.stock_current > 0
               AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
             ORDER BY stock.item_stock_id_imei ASC",
        );
        $imeiStmt->execute([
            "item_id" => $itemId,
            "shop_id" => $shopId,
        ]);
        foreach ($imeiStmt->fetchAll() as $row) {
            $rows[] = $this->normalizeCandidateRow($row);
        }

        $rcvStmt = $this->db->prepare(
            "SELECT 3 AS object_type,
                    stock.recordid AS row_id,
                    stock.card_name AS item_name,
                    '' AS item_code,
                    stock.current_stock AS stock_current,
                    stock.cost_price AS part_cost
             FROM shop_rcv_stock AS stock
             WHERE stock.item_name_id = :item_id
               AND stock.stock_status = 1
               AND stock.current_stock > 0
               AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
             ORDER BY stock.recordid ASC",
        );
        $rcvStmt->execute([
            "item_id" => $itemId,
            "shop_id" => $shopId,
        ]);
        foreach ($rcvStmt->fetchAll() as $row) {
            $rows[] = $this->normalizeCandidateRow($row);
        }

        return $rows;
    }

    public function addDraftLine(array $auth, int $objectType, int $rowId, int $qty): void
    {
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $draft = $this->draft($auth);
        $candidate = $this->findCandidateByRow($objectType, $rowId, $shopId);

        if ($candidate === null) {
            throw new RuntimeException("Stock record was not found for transfer.");
        }

        $currentStock = (int) ($candidate["stock_current"] ?? 0);
        if ($qty < 1 || $qty > $currentStock) {
            throw new RuntimeException("Transfer quantity must be at least one and cannot exceed current stock.");
        }

        if ($objectType === 2 && $qty !== 1) {
            throw new RuntimeException("IMEI stock must be transferred one by one.");
        }

        foreach ($draft["lines"] as $line) {
            if ((int) ($line["object_type"] ?? 0) === $objectType && (int) ($line["row_id"] ?? 0) === $rowId) {
                throw new RuntimeException("This stock record is already staged in the current transfer.");
            }
        }

        $draft["lines"][] = [
            "object_type" => $objectType,
            "row_id" => $rowId,
            "item_name" => (string) ($candidate["item_name"] ?? ""),
            "item_code" => (string) ($candidate["item_code"] ?? ""),
            "stock_current" => $currentStock,
            "trans_amount" => $qty,
        ];

        $_SESSION[$this->draftSessionKey($auth)] = $draft;
    }

    public function updateDraftLine(array $auth, int $index, int $qty): void
    {
        $draft = $this->draft($auth);
        if (!isset($draft["lines"][$index])) {
            throw new RuntimeException("Draft transfer line was not found.");
        }

        $line = $draft["lines"][$index];
        $max = (int) ($line["stock_current"] ?? 0);
        if ($qty < 1 || $qty > $max) {
            throw new RuntimeException("Transfer quantity must be at least one and cannot exceed current stock.");
        }
        if ((int) ($line["object_type"] ?? 0) === 2 && $qty !== 1) {
            throw new RuntimeException("IMEI stock must be transferred one by one.");
        }

        $draft["lines"][$index]["trans_amount"] = $qty;
        $_SESSION[$this->draftSessionKey($auth)] = $draft;
    }

    public function removeDraftLine(array $auth, int $index): void
    {
        $draft = $this->draft($auth);
        if (!isset($draft["lines"][$index])) {
            throw new RuntimeException("Draft transfer line was not found.");
        }

        array_splice($draft["lines"], $index, 1);
        $_SESSION[$this->draftSessionKey($auth)] = $draft;
    }

    public function submitDraft(array $auth): string
    {
        $draft = $this->draft($auth);
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $userId = (int) ($auth["user_id"] ?? 0);
        $targetShopId = (int) ($draft["target_shop_id"] ?? 0);
        $lines = $draft["lines"] ?? [];

        if ($targetShopId < 1) {
            throw new RuntimeException("Select a target shop before creating a transfer.");
        }
        if ($shopId > 0 && $shopId === $targetShopId) {
            throw new RuntimeException("Transfer target shop must be different from the current shop.");
        }
        if ($lines === []) {
            throw new RuntimeException("Add stock lines before creating a transfer.");
        }

        $yearMonthFull = date("Ym");
        $shortMonth = substr($yearMonthFull, 2);
        $recordTime = date("Y-m-d H:i:s");

        $this->db->beginTransaction();
        try {
            $seqStmt = $this->db->prepare(
                "SELECT COALESCE(MAX(trans_sequ), 0)
                 FROM stock_transmain
                 WHERE trans_month = :trans_month
                 FOR UPDATE",
            );
            $seqStmt->execute(["trans_month" => $yearMonthFull]);
            $nextSeq = ((int) $seqStmt->fetchColumn()) + 1;
            $transferId = "TRS" . $shortMonth . str_pad((string) $nextSeq, 3, "0", STR_PAD_LEFT) . $shopId;

            $insertMain = $this->db->prepare(
                "INSERT INTO stock_transmain
                 (trans_id, trans_month, short_month, trans_sequ, trans_fromshop, processed_operator, trans_status, record_time)
                 VALUES
                 (:trans_id, :trans_month, :short_month, :trans_sequ, :trans_fromshop, :processed_operator, 1, :record_time)",
            );
            $insertMain->execute([
                "trans_id" => $transferId,
                "trans_month" => $yearMonthFull,
                "short_month" => $shortMonth,
                "trans_sequ" => $nextSeq,
                "trans_fromshop" => $shopId,
                "processed_operator" => $userId,
                "record_time" => $recordTime,
            ]);

            $totalItems = 0;
            $totalCost = 0.0;
            foreach ($lines as $line) {
                $saved = $this->moveDraftLineToTransfer($line, $targetShopId, $userId, $transferId, $recordTime);
                $totalItems += $saved["qty"];
                $totalCost += $saved["transfer_value"];
            }

            $updateMain = $this->db->prepare(
                "UPDATE stock_transmain
                 SET total_cost = :total_cost,
                     item_count = :item_count
                 WHERE trans_id = :trans_id",
            );
            $updateMain->execute([
                "total_cost" => $totalCost,
                "item_count" => $totalItems,
                "trans_id" => $transferId,
            ]);

            $this->db->commit();
            $this->clearDraft($auth);

            return $transferId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function outgoing(int $authShopId): array
    {
        $sql = "SELECT main.*,
                       from_shop.shop_info_name AS from_shop_name,
                       user.visibledata AS processed_operator_name
                FROM stock_transmain AS main
                LEFT JOIN sys_shop AS from_shop
                  ON from_shop.shopid = main.trans_fromshop
                LEFT JOIN sys_user AS user
                  ON user.myid = main.processed_operator
                WHERE 1 = 1";
        $params = [];

        if ($authShopId > 0) {
            $sql .= " AND main.trans_fromshop = :shop_id";
            $params["shop_id"] = $authShopId;
        }

        $sql .= " AND main.trans_status < 5 ORDER BY main.record_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $this->attachLogs($rows);
        return $rows;
    }

    public function incomingPending(int $shopId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT main.*,
                    from_shop.shop_info_name AS from_shop_name,
                    user.visibledata AS processed_operator_name
             FROM stock_transmain AS main
             INNER JOIN stock_translog AS log
               ON log.trans_id = main.trans_id
             LEFT JOIN sys_shop AS from_shop
               ON from_shop.shopid = main.trans_fromshop
             LEFT JOIN sys_user AS user
               ON user.myid = main.processed_operator
             WHERE log.to_shop = :shop_id
               AND main.trans_status = 2
             ORDER BY main.record_id DESC",
        );
        $stmt->execute(["shop_id" => $shopId]);
        $rows = $stmt->fetchAll();

        $this->attachLogs($rows);
        return $rows;
    }

    public function markInTransit(string $transferId, int $authShopId): void
    {
        $transfer = $this->findByTransferId($transferId, $authShopId, false);
        if ($transfer === null) {
            throw new RuntimeException("Transfer note was not found.");
        }

        if ((int) ($transfer["trans_status"] ?? 0) !== 1) {
            throw new RuntimeException("Only newly created transfer notes can be marked in transit.");
        }

        $stmt = $this->db->prepare(
            "UPDATE stock_transmain
             SET trans_status = 2
             WHERE trans_id = :trans_id
             LIMIT 1",
        );
        $stmt->execute(["trans_id" => $transferId]);
    }

    public function acceptReceived(string $transferId, int $shopId): void
    {
        $transfer = $this->findByTransferId($transferId, $shopId, true);
        if ($transfer === null) {
            throw new RuntimeException("Pending transfer was not found for this shop.");
        }

        if ((int) ($transfer["trans_status"] ?? 0) !== 2) {
            throw new RuntimeException("Only in-transit transfer notes can be accepted.");
        }

        $this->db->beginTransaction();

        try {
            $updateMain = $this->db->prepare(
                "UPDATE stock_transmain
                 SET trans_status = 4
                 WHERE trans_id = :trans_id
                 LIMIT 1",
            );
            $updateMain->execute(["trans_id" => $transferId]);

            $logStmt = $this->db->prepare(
                "SELECT *
                 FROM stock_translog
                 WHERE trans_id = :trans_id",
            );
            $logStmt->execute(["trans_id" => $transferId]);

            foreach ($logStmt->fetchAll() as $log) {
                $type = (int) ($log["item_type"] ?? 0);
                $code = (string) ($log["code"] ?? "");
                $toShop = (int) ($log["to_shop"] ?? 0);
                $count = (int) ($log["stock_count"] ?? 0);
                $recordedTime = (string) ($log["recorded_time"] ?? "");

                if ($type === 1) {
                    $updateStock = $this->db->prepare(
                        "UPDATE shop_stock_item
                         SET stock_status = 1
                         WHERE gen_refno = :code
                           AND stock_in_shop = :shop
                           AND stock_current = :count
                           AND stock_add_dt = :recorded_time",
                    );
                    $updateStock->execute([
                        "code" => $code,
                        "shop" => $toShop,
                        "count" => $count,
                        "recorded_time" => $recordedTime,
                    ]);
                } elseif ($type === 2) {
                    $updateStock = $this->db->prepare(
                        "UPDATE shop_stock_imei
                         SET stock_status = 1
                         WHERE imei_no = :code
                           AND stock_in_shop = :shop
                           AND stock_current = :count
                           AND stock_add_dt = :recorded_time",
                    );
                    $updateStock->execute([
                        "code" => $code,
                        "shop" => $toShop,
                        "count" => $count,
                        "recorded_time" => $recordedTime,
                    ]);
                }
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function raiseComplaint(string $transferId, int $shopId, int $userId, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === "") {
            throw new RuntimeException("Complaint reason is required.");
        }

        $transfer = $this->findByTransferId($transferId, $shopId, true);
        if ($transfer === null) {
            throw new RuntimeException("Pending transfer was not found for this shop.");
        }

        if ((int) ($transfer["trans_status"] ?? 0) !== 2) {
            throw new RuntimeException("Only in-transit transfer notes can be raised as complaints.");
        }

        $this->db->beginTransaction();
        try {
            $insert = $this->db->prepare(
                "INSERT INTO shop_transerror
                 (job_number, comp_reason, comp_status, complain_user, record_time)
                 VALUES
                 (:job_number, :comp_reason, 1, :complain_user, :record_time)",
            );
            $insert->execute([
                "job_number" => $transferId,
                "comp_reason" => $reason,
                "complain_user" => $userId,
                "record_time" => date("Y-m-d H:i:s"),
            ]);

            $update = $this->db->prepare(
                "UPDATE stock_transmain
                 SET trans_status = 3
                 WHERE trans_id = :trans_id
                 LIMIT 1",
            );
            $update->execute(["trans_id" => $transferId]);

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function complaintQueue(int $authShopId): array
    {
        $sql = "SELECT main.*,
                       from_shop.shop_info_name AS from_shop_name,
                       user.visibledata AS processed_operator_name,
                       err.comp_reason,
                       err.comp_status,
                       err.complain_user,
                       err.record_time AS complaint_time,
                       err.recover_note,
                       err.recover_action,
                       err.update_time,
                       complainant.visibledata AS complain_user_name
                FROM stock_transmain AS main
                INNER JOIN shop_transerror AS err
                  ON err.job_number = main.trans_id
                LEFT JOIN sys_shop AS from_shop
                  ON from_shop.shopid = main.trans_fromshop
                LEFT JOIN sys_user AS user
                  ON user.myid = main.processed_operator
                LEFT JOIN sys_user AS complainant
                  ON complainant.myid = err.complain_user
                WHERE main.trans_status = 3";
        $params = [];

        if ($authShopId > 0) {
            $sql .= " AND main.trans_fromshop = :shop_id";
            $params["shop_id"] = $authShopId;
        }

        $sql .= " ORDER BY main.record_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $this->attachLogs($rows);

        foreach ($rows as &$row) {
            $row["complaint_status_label"] = (int) ($row["comp_status"] ?? 0) === 2 ? "Updated" : "Action Pending";
        }
        unset($row);

        return $rows;
    }

    public function resolveComplaint(string $transferId, string $note, string $action, int $authShopId): void
    {
        $transfer = $this->findByTransferId($transferId, $authShopId, false);
        if ($transfer === null || (int) ($transfer["trans_status"] ?? 0) !== 3) {
            throw new RuntimeException("Complaint transfer record was not found.");
        }

        $note = trim($note);
        if ($note === "") {
            throw new RuntimeException("Recovery note is required.");
        }

        if (!in_array($action, ["release", "discard"], true)) {
            throw new RuntimeException("Invalid recovery action.");
        }

        $this->db->beginTransaction();
        try {
            $recoverAction = $action === "release"
                ? "Release Stock with New Stock Amount"
                : "Discard Stock";

            $updateErr = $this->db->prepare(
                "UPDATE shop_transerror
                 SET recover_note = :recover_note,
                     recover_action = :recover_action,
                     comp_status = 2,
                     update_time = :update_time
                 WHERE job_number = :job_number",
            );
            $updateErr->execute([
                "recover_note" => $note,
                "recover_action" => $recoverAction,
                "update_time" => date("Y-m-d H:i:s"),
                "job_number" => $transferId,
            ]);

            $updateMain = $this->db->prepare(
                "UPDATE stock_transmain
                 SET trans_status = 5
                 WHERE trans_id = :trans_id",
            );
            $updateMain->execute(["trans_id" => $transferId]);

            $logStmt = $this->db->prepare(
                "SELECT * FROM stock_translog WHERE trans_id = :trans_id",
            );
            $logStmt->execute(["trans_id" => $transferId]);

            foreach ($logStmt->fetchAll() as $log) {
                $type = (int) ($log["item_type"] ?? 0);
                $code = (string) ($log["code"] ?? "");
                $toShop = (int) ($log["to_shop"] ?? 0);

                if ($type === 1) {
                    $stmt = $this->db->prepare(
                        "UPDATE shop_stock_item
                         SET stock_status = :stock_status
                         WHERE gen_refno = :code
                           AND stock_status = 2
                           AND stock_in_shop = :shop_id",
                    );
                    $stmt->execute([
                        "stock_status" => $action === "release" ? 1 : 4,
                        "code" => $code,
                        "shop_id" => $toShop,
                    ]);
                } elseif ($type === 2) {
                    $stmt = $this->db->prepare(
                        "UPDATE shop_stock_imei
                         SET stock_status = :stock_status
                         WHERE imei_no = :code
                           AND stock_status = 2
                           AND stock_in_shop = :shop_id",
                    );
                    $stmt->execute([
                        "stock_status" => $action === "release" ? 1 : 4,
                        "code" => $code,
                        "shop_id" => $toShop,
                    ]);
                }
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function findWithLogs(string $transferId): ?array
    {
        $sql = "SELECT main.*,
                       from_shop.shop_info_name AS from_shop_name,
                       user.visibledata AS processed_operator_name
                FROM stock_transmain AS main
                LEFT JOIN sys_shop AS from_shop
                  ON from_shop.shopid = main.trans_fromshop
                LEFT JOIN sys_user AS user
                  ON user.myid = main.processed_operator
                WHERE main.trans_id = :trans_id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["trans_id" => $transferId]);
        $row = $stmt->fetch();
        
        if ($row === false) {
            return null;
        }
        
        $rows = [$row];
        $this->attachLogs($rows);
        return $rows[0];
    }

    private function findByTransferId(string $transferId, int $shopId, bool $incoming): ?array
    {
        if ($incoming) {
            $stmt = $this->db->prepare(
                "SELECT DISTINCT main.*
                 FROM stock_transmain AS main
                 INNER JOIN stock_translog AS log
                   ON log.trans_id = main.trans_id
                 WHERE main.trans_id = :trans_id
                   AND log.to_shop = :shop_id
                 LIMIT 1",
            );
            $stmt->execute([
                "trans_id" => $transferId,
                "shop_id" => $shopId,
            ]);
        } else {
            $sql = "SELECT *
                    FROM stock_transmain
                    WHERE trans_id = :trans_id";
            $params = ["trans_id" => $transferId];
            if ($shopId > 0) {
                $sql .= " AND trans_fromshop = :shop_id";
                $params["shop_id"] = $shopId;
            }
            $sql .= " LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        }

        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    private function attachLogs(array &$rows): void
    {
        if ($rows === []) {
            return;
        }

        $params = [];
        $placeholders = [];
        foreach ($rows as $index => $row) {
            $key = "trans_" . $index;
            $placeholders[] = ":" . $key;
            $params[$key] = (string) ($row["trans_id"] ?? "");
        }

        $stmt = $this->db->prepare(
            "SELECT log.*,
                    from_shop.shop_info_name AS from_shop_name,
                    to_shop.shop_info_name AS to_shop_name
             FROM stock_translog AS log
             LEFT JOIN sys_shop AS from_shop
               ON from_shop.shopid = log.from_shop
             LEFT JOIN sys_shop AS to_shop
               ON to_shop.shopid = log.to_shop
             WHERE log.trans_id IN (" . implode(", ", $placeholders) . ")
             ORDER BY log.record_id ASC",
        );
        $stmt->execute($params);

        $grouped = [];
        foreach ($stmt->fetchAll() as $log) {
            $grouped[(string) ($log["trans_id"] ?? "")][] = $log;
        }

        foreach ($rows as &$row) {
            $row["logs"] = $grouped[(string) ($row["trans_id"] ?? "")] ?? [];
            $row["status_label"] = match ((int) ($row["trans_status"] ?? 0)) {
                1 => "Created Only",
                2 => "In Transit",
                3 => "Complaint Raised",
                4 => "Accepted",
                default => "Unknown",
            };
        }
        unset($row);
    }

    private function findCandidateByRow(int $objectType, int $rowId, int $shopId): ?array
    {
        if ($objectType === 1) {
            $stmt = $this->db->prepare(
                "SELECT 1 AS object_type,
                        stock.item_stock_id AS row_id,
                        stock.item_name,
                        stock.gen_refno AS item_code,
                        stock.stock_current,
                        stock.item_cost_price AS part_cost
                 FROM shop_stock_item AS stock
                 WHERE stock.item_stock_id = :row_id
                   AND stock.stock_status = 1
                   AND stock.stock_current > 0
                   AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
                 LIMIT 1",
            );
        } elseif ($objectType === 2) {
            $stmt = $this->db->prepare(
                "SELECT 2 AS object_type,
                        stock.item_stock_id_imei AS row_id,
                        stock.item_name,
                        stock.imei_no AS item_code,
                        stock.stock_current,
                        stock.item_cost_price AS part_cost
                 FROM shop_stock_imei AS stock
                 WHERE stock.item_stock_id_imei = :row_id
                   AND stock.stock_status = 1
                   AND stock.stock_current > 0
                   AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
                 LIMIT 1",
            );
        } else {
            $stmt = $this->db->prepare(
                "SELECT 3 AS object_type,
                        stock.recordid AS row_id,
                        stock.card_name AS item_name,
                        '' AS item_code,
                        stock.current_stock AS stock_current,
                        stock.cost_price AS part_cost
                 FROM shop_rcv_stock AS stock
                 WHERE stock.recordid = :row_id
                   AND stock.stock_status = 1
                   AND stock.current_stock > 0
                   AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
                 LIMIT 1",
            );
        }

        $stmt->execute([
            "row_id" => $rowId,
            "shop_id" => $shopId,
        ]);
        $row = $stmt->fetch();

        return $row !== false ? $this->normalizeCandidateRow($row) : null;
    }

    private function moveDraftLineToTransfer(array $line, int $targetShopId, int $userId, string $transferId, string $recordTime): array
    {
        $type = (int) ($line["object_type"] ?? 0);
        $rowId = (int) ($line["row_id"] ?? 0);
        $qty = (int) ($line["trans_amount"] ?? 0);

        if ($type === 1) {
            $stockStmt = $this->db->prepare(
                "SELECT * FROM shop_stock_item WHERE item_stock_id = :row_id AND stock_current >= :qty LIMIT 1 FOR UPDATE",
            );
            $stockStmt->execute(["row_id" => $rowId, "qty" => $qty]);
            $stock = $stockStmt->fetch();
            if ($stock === false) {
                throw new RuntimeException("A barcode stock line is no longer available for transfer.");
            }

            $newOldStock = (int) $stock["stock_current"] - $qty;
            $insert = $this->db->prepare(
                "INSERT INTO shop_stock_item
                 (grn_refno, gen_refno, supplier_id, valied_month, fixed_yy, gen_seq, stock_added, stock_current, stock_status, item_cat, item_name, item_cat_id, item_name_id, item_color, item_cost_price, item_sell_price, item_free, item_low_price, item_other_price, warranty_span, warranty_type, stock_in_shop, barcode_print, trans_uid, stock_add_dt)
                 VALUES
                 (:grn_refno, :gen_refno, :supplier_id, :valied_month, :fixed_yy, :gen_seq, :stock_added, :stock_current, 2, :item_cat, :item_name, :item_cat_id, :item_name_id, :item_color, :item_cost_price, :item_sell_price, :item_free, :item_low_price, :item_other_price, :warranty_span, :warranty_type, :stock_in_shop, :barcode_print, :trans_uid, :stock_add_dt)",
            );
            $insert->execute([
                "grn_refno" => $stock["grn_refno"],
                "gen_refno" => $stock["gen_refno"],
                "supplier_id" => $stock["supplier_id"],
                "valied_month" => $stock["valied_month"],
                "fixed_yy" => $stock["fixed_yy"],
                "gen_seq" => $stock["gen_seq"],
                "stock_added" => $stock["stock_added"],
                "stock_current" => $qty,
                "item_cat" => $stock["item_cat"],
                "item_name" => $stock["item_name"],
                "item_cat_id" => $stock["item_cat_id"],
                "item_name_id" => $stock["item_name_id"],
                "item_color" => $stock["item_color"],
                "item_cost_price" => $stock["item_cost_price"],
                "item_sell_price" => $stock["item_sell_price"],
                "item_free" => $stock["item_free"],
                "item_low_price" => $stock["item_low_price"],
                "item_other_price" => $stock["item_other_price"],
                "warranty_span" => $stock["warranty_span"],
                "warranty_type" => $stock["warranty_type"],
                "stock_in_shop" => $targetShopId,
                "barcode_print" => $stock["barcode_print"],
                "trans_uid" => $userId,
                "stock_add_dt" => $recordTime,
            ]);

            $updateOld = $this->db->prepare(
                "UPDATE shop_stock_item SET stock_current = :stock_current WHERE item_stock_id = :row_id",
            );
            $updateOld->execute([
                "stock_current" => $newOldStock,
                "row_id" => $rowId,
            ]);

            $transferValue = (float) $stock["item_cost_price"] * $qty;
            $this->insertTransferLog($transferId, 1, (string) $stock["item_name"], (string) $stock["gen_refno"], (int) $stock["stock_in_shop"], $qty, $targetShopId, (float) $stock["item_cost_price"], $transferValue, $recordTime);

            return ["qty" => $qty, "transfer_value" => $transferValue];
        }

        if ($type === 2) {
            $stockStmt = $this->db->prepare(
                "SELECT * FROM shop_stock_imei WHERE item_stock_id_imei = :row_id AND stock_current >= :qty LIMIT 1 FOR UPDATE",
            );
            $stockStmt->execute(["row_id" => $rowId, "qty" => $qty]);
            $stock = $stockStmt->fetch();
            if ($stock === false) {
                throw new RuntimeException("An IMEI stock line is no longer available for transfer.");
            }

            $newOldStock = (int) $stock["stock_current"] - $qty;
            $insert = $this->db->prepare(
                "INSERT INTO shop_stock_imei
                 (grn_refno, supplier_id, stock_current, imei_no, item_cat, item_name, item_cat_id, item_name_id, item_color, item_cost_price, item_sell_price, item_free, item_low_price, item_other_price, warranty_span, warranty_type, stock_in_shop, stock_status, stock_add_dt)
                 VALUES
                 (:grn_refno, :supplier_id, :stock_current, :imei_no, :item_cat, :item_name, :item_cat_id, :item_name_id, :item_color, :item_cost_price, :item_sell_price, :item_free, :item_low_price, :item_other_price, :warranty_span, :warranty_type, :stock_in_shop, 2, :stock_add_dt)",
            );
            $insert->execute([
                "grn_refno" => $stock["grn_refno"],
                "supplier_id" => $stock["supplier_id"],
                "stock_current" => $qty,
                "imei_no" => $stock["imei_no"],
                "item_cat" => $stock["item_cat"],
                "item_name" => $stock["item_name"],
                "item_cat_id" => $stock["item_cat_id"],
                "item_name_id" => $stock["item_name_id"],
                "item_color" => $stock["item_color"],
                "item_cost_price" => $stock["item_cost_price"],
                "item_sell_price" => $stock["item_sell_price"],
                "item_free" => $stock["item_free"],
                "item_low_price" => $stock["item_low_price"],
                "item_other_price" => $stock["item_other_price"],
                "warranty_span" => $stock["warranty_span"],
                "warranty_type" => $stock["warranty_type"],
                "stock_in_shop" => $targetShopId,
                "stock_add_dt" => $recordTime,
            ]);

            $updateOld = $this->db->prepare(
                "UPDATE shop_stock_imei SET stock_current = :stock_current WHERE item_stock_id_imei = :row_id",
            );
            $updateOld->execute([
                "stock_current" => $newOldStock,
                "row_id" => $rowId,
            ]);

            $transferValue = (float) $stock["item_cost_price"] * $qty;
            $this->insertTransferLog($transferId, 2, (string) $stock["item_name"], (string) $stock["imei_no"], (int) $stock["stock_in_shop"], $qty, $targetShopId, (float) $stock["item_cost_price"], $transferValue, $recordTime);

            return ["qty" => $qty, "transfer_value" => $transferValue];
        }

        $stockStmt = $this->db->prepare(
            "SELECT * FROM shop_rcv_stock WHERE recordid = :row_id AND current_stock >= :qty LIMIT 1 FOR UPDATE",
        );
        $stockStmt->execute(["row_id" => $rowId, "qty" => $qty]);
        $stock = $stockStmt->fetch();
        if ($stock === false) {
            throw new RuntimeException("A recharge stock line is no longer available for transfer.");
        }

        $newOldStock = (int) $stock["current_stock"] - $qty;
        $targetStmt = $this->db->prepare(
            "SELECT * FROM shop_rcv_stock WHERE card_id = :card_id AND stock_in_shop = :shop_id LIMIT 1 FOR UPDATE",
        );
        $targetStmt->execute([
            "card_id" => $stock["card_id"],
            "shop_id" => $targetShopId,
        ]);
        $target = $targetStmt->fetch();

        if ($target !== false) {
            $updateTarget = $this->db->prepare(
                "UPDATE shop_rcv_stock SET current_stock = :current_stock WHERE recordid = :recordid",
            );
            $updateTarget->execute([
                "current_stock" => ((int) $target["current_stock"]) + $qty,
                "recordid" => $target["recordid"],
            ]);
        } else {
            $insertTarget = $this->db->prepare(
                "INSERT INTO shop_rcv_stock
                 (card_id, card_name, item_cat_id, item_name_id, current_stock, min_limit, stock_status, cost_price, sell_price, low_price, other_price, stock_in_shop, last_upd)
                 VALUES
                 (:card_id, :card_name, :item_cat_id, :item_name_id, :current_stock, :min_limit, :stock_status, :cost_price, :sell_price, :low_price, :other_price, :stock_in_shop, :last_upd)",
            );
            $insertTarget->execute([
                "card_id" => $stock["card_id"],
                "card_name" => $stock["card_name"],
                "item_cat_id" => $stock["item_cat_id"],
                "item_name_id" => $stock["item_name_id"],
                "current_stock" => $qty,
                "min_limit" => $stock["min_limit"],
                "stock_status" => $stock["stock_status"],
                "cost_price" => $stock["cost_price"],
                "sell_price" => $stock["sell_price"],
                "low_price" => $stock["low_price"],
                "other_price" => $stock["other_price"],
                "stock_in_shop" => $targetShopId,
                "last_upd" => $recordTime,
            ]);
        }

        $updateOld = $this->db->prepare(
            "UPDATE shop_rcv_stock SET current_stock = :current_stock WHERE recordid = :row_id",
        );
        $updateOld->execute([
            "current_stock" => $newOldStock,
            "row_id" => $rowId,
        ]);

        $transferValue = (float) $stock["cost_price"] * $qty;
        $this->insertTransferLog($transferId, 3, (string) $stock["card_name"], (string) $rowId, (int) $stock["stock_in_shop"], $qty, $targetShopId, (float) $stock["cost_price"], $transferValue, $recordTime);

        return ["qty" => $qty, "transfer_value" => $transferValue];
    }

    private function insertTransferLog(string $transferId, int $itemType, string $itemName, string $code, int $fromShop, int $qty, int $toShop, float $partCost, float $transferValue, string $recordTime): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO stock_translog
             (trans_id, item_type, Item_name, code, from_shop, stock_count, to_shop, part_cost, transfer_value, recorded_time)
             VALUES
             (:trans_id, :item_type, :item_name, :code, :from_shop, :stock_count, :to_shop, :part_cost, :transfer_value, :recorded_time)",
        );
        $stmt->execute([
            "trans_id" => $transferId,
            "item_type" => $itemType,
            "item_name" => $itemName,
            "code" => $code,
            "from_shop" => $fromShop,
            "stock_count" => $qty,
            "to_shop" => $toShop,
            "part_cost" => $partCost,
            "transfer_value" => $transferValue,
            "recorded_time" => $recordTime,
        ]);
    }

    private function normalizeCandidateRow(array $row): array
    {
        return [
            "object_type" => (int) ($row["object_type"] ?? 0),
            "row_id" => (int) ($row["row_id"] ?? 0),
            "item_name" => (string) ($row["item_name"] ?? ""),
            "item_code" => (string) ($row["item_code"] ?? ""),
            "stock_current" => (int) ($row["stock_current"] ?? 0),
            "part_cost" => (float) ($row["part_cost"] ?? 0),
        ];
    }

    private function draftSessionKey(array $auth): string
    {
        return "stock_transfer_draft_" . (int) ($auth["user_id"] ?? 0) . "_" . (int) ($auth["shop_id"] ?? 0);
    }
}
