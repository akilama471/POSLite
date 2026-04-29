<?php

declare(strict_types=1);

class RechargeOperator extends Model
{
    protected $table = "shop_rcv_operator";

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM shop_rcv_operator ORDER BY recordid ASC",
        );

        return $stmt->fetchAll();
    }

    public function activeOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM shop_rcv_operator WHERE status = 1 ORDER BY operator_name ASC",
        );

        return $stmt->fetchAll();
    }

    public function findById(int $operatorId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM shop_rcv_operator WHERE recordid = :recordid LIMIT 1",
        );
        $stmt->execute(["recordid" => $operatorId]);

        $operator = $stmt->fetch();
        return $operator ?: null;
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM shop_rcv_operator WHERE operator_name = :name";
        $params = ["name" => $name];

        if ($excludeId !== null) {
            $sql .= " AND recordid <> :recordid";
            $params["recordid"] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createOperator(string $name): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO shop_rcv_operator (operator_name, status, eff_date)
             VALUES (:operator_name, 1, :eff_date)",
        );

        return $stmt->execute([
            "operator_name" => $name,
            "eff_date" => date("Y-m-d H:i:s"),
        ]);
    }

    public function updateOperator(int $operatorId, string $name): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE shop_rcv_operator
             SET operator_name = :operator_name
             WHERE recordid = :recordid",
        );

        return $stmt->execute([
            "recordid" => $operatorId,
            "operator_name" => $name,
        ]);
    }
}
