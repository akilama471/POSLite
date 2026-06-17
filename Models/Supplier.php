<?php

declare(strict_types=1);

class Supplier extends Model
{
    protected $table = "shop_supplier";

    private function mapLegacyFields(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        if (isset($row['id']) && !isset($row['supplierid'])) {
            $row['supplierid'] = $row['id'];
        }

        if (isset($row['sup_name'])) {
            $row['supplier_name'] = $row['sup_name'];
        }

        if (isset($row['sup_mobile'])) {
            $row['supplier_mobile'] = $row['sup_mobile'];
        }

        if (isset($row['sup_address'])) {
            $row['supplier_address'] = $row['sup_address'];
        }

        if (isset($row['status'])) {
            $row['supplier_status'] = $row['status'];
        }

        return $row;
    }

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM shop_supplier ORDER BY id ASC",
        );

        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapLegacyFields'], $rows);
    }

    public function findById(int $supplierId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM shop_supplier WHERE id = :supplierid LIMIT 1",
        );
        $stmt->execute(["supplierid" => $supplierId]);

        $supplier = $stmt->fetch();
        return $this->mapLegacyFields($supplier ?: null);
    }

    public function existsByName(string $name): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM shop_supplier WHERE sup_name = :name",
        );
        $stmt->execute(["name" => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createSupplier(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO shop_supplier
            (sup_name, sup_mobile, sup_address, status, eff_date)
            VALUES
            (:sup_name, :sup_mobile, :sup_address, 1, :eff_date)",
        );

        return $stmt->execute([
            "sup_name" => $data["supplier_name"],
            "sup_mobile" => $data["supplier_mobile"],
            "sup_address" => $data["supplier_address"],
            "eff_date" => $data["eff_date"] ?? date("Y-m-d H:i:s"),
        ]);
    }

    public function updateSupplier(int $supplierId, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE shop_supplier SET
                sup_name = :sup_name,
                sup_mobile = :sup_mobile,
                sup_address = :sup_address
             WHERE id = :supplierid",
        );

        return $stmt->execute([
            "supplierid" => $supplierId,
            "sup_name" => $data["supplier_name"],
            "sup_mobile" => $data["supplier_mobile"],
            "sup_address" => $data["supplier_address"],
        ]);
    }

    public function allWithBalances(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM shop_supplier ORDER BY id ASC",
        );

        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapLegacyFields'], $rows);
    }

    public function allWithCashCreditBalances(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM shop_supplier ORDER BY id ASC",
        );

        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapLegacyFields'], $rows);
    }
}
