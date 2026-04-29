<?php

declare(strict_types=1);

class Item extends Model
{
    protected $table = "prod_items";

    public function createContext(): array
    {
        $categoryModel = new ProductCategory();
        $operatorModel = new RechargeOperator();

        return [
            "categories" => $categoryModel->allOrdered(),
            "operators" => $operatorModel->activeOrdered(),
            "existingItems" => $this->allOrdered(),
        ];
    }

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM prod_items ORDER BY item_name ASC",
        );

        return $stmt->fetchAll();
    }

    public function searchDetailed(string $term = ""): array
    {
        $stmt = $this->db->prepare(
            "SELECT item.*,
                    category.catname AS category_name,
                    card.remark AS card_remark,
                    card.operator AS operator_id,
                    operator.operator_name
             FROM prod_items AS item
             LEFT JOIN prod_category AS category
               ON category.catid = item.item_cat
             LEFT JOIN shop_rcv_cards AS card
               ON card.prod_id = item.item_id
             LEFT JOIN shop_rcv_operator AS operator
               ON operator.recordid = card.operator
             WHERE item.item_name LIKE :term
             ORDER BY item.item_name ASC",
        );
        $stmt->execute(["term" => "%" . $term . "%"]);

        return $stmt->fetchAll();
    }

    public function findDetailedById(int $itemId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT item.*,
                    category.catname AS category_name,
                    card.remark AS card_remark,
                    card.operator AS operator_id,
                    operator.operator_name
             FROM prod_items AS item
             LEFT JOIN prod_category AS category
               ON category.catid = item.item_cat
             LEFT JOIN shop_rcv_cards AS card
               ON card.prod_id = item.item_id
             LEFT JOIN shop_rcv_operator AS operator
               ON operator.recordid = card.operator
             WHERE item.item_id = :item_id
             LIMIT 1",
        );
        $stmt->execute(["item_id" => $itemId]);

        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM prod_items WHERE item_name = :name";
        $params = ["name" => $name];

        if ($excludeId !== null) {
            $sql .= " AND item_id <> :item_id";
            $params["item_id"] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createItem(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $itemStmt = $this->db->prepare(
                "INSERT INTO prod_items (item_name, item_cat, used_type, eff_date)
                 VALUES (:item_name, :item_cat, :used_type, :eff_date)",
            );
            $itemStmt->execute([
                "item_name" => $data["item_name"],
                "item_cat" => $data["item_cat"],
                "used_type" => $data["used_type"],
                "eff_date" => $data["eff_date"],
            ]);

            $itemId = (int) $this->db->lastInsertId();

            if ((int) $data["used_type"] === 3) {
                $cardStmt = $this->db->prepare(
                    "INSERT INTO shop_rcv_cards (cat_id, prod_id, card_name, operator, remark, status, eff_date)
                     VALUES (:cat_id, :prod_id, :card_name, :operator, :remark, 1, :eff_date)",
                );
                $cardStmt->execute([
                    "cat_id" => $data["item_cat"],
                    "prod_id" => $itemId,
                    "card_name" => $data["item_name"],
                    "operator" => $data["operator_id"],
                    "remark" => $data["card_remark"],
                    "eff_date" => $data["eff_date"],
                ]);
            }

            $this->db->commit();
            return $itemId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateItem(int $itemId, array $data): bool
    {
        $this->db->beginTransaction();

        try {
            $itemStmt = $this->db->prepare(
                "UPDATE prod_items
                 SET item_name = :item_name
                 WHERE item_id = :item_id",
            );
            $itemStmt->execute([
                "item_id" => $itemId,
                "item_name" => $data["item_name"],
            ]);

            if ((int) $data["used_type"] === 3) {
                $cardStmt = $this->db->prepare(
                    "UPDATE shop_rcv_cards
                     SET card_name = :card_name,
                         remark = :remark
                     WHERE prod_id = :prod_id",
                );
                $cardStmt->execute([
                    "prod_id" => $itemId,
                    "card_name" => $data["item_name"],
                    "remark" => $data["card_remark"],
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public static function typeLabel(int $usedType): string
    {
        return match ($usedType) {
            1 => "By Item Code",
            2 => "By IMEI Number",
            3 => "By Recharge Card",
            default => "Unknown",
        };
    }
}
