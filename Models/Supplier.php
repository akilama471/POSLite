<?php

declare(strict_types=1);

class Supplier extends Model
{
    protected $table = "shop_supplier";

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM shop_supplier ORDER BY supplierid ASC",
        );

        return $stmt->fetchAll();
    }

    public function findById(int $supplierId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM shop_supplier WHERE supplierid = :supplierid LIMIT 1",
        );
        $stmt->execute(["supplierid" => $supplierId]);

        $supplier = $stmt->fetch();
        return $supplier ?: null;
    }

    public function existsByName(string $name): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM shop_supplier WHERE supplier_name = :name",
        );
        $stmt->execute(["name" => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createSupplier(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO shop_supplier
            (supplier_name, supplier_mobile, supplier_address, supplier_status, eff_date)
            VALUES
            (:supplier_name, :supplier_mobile, :supplier_address, 1, :eff_date)",
        );

        return $stmt->execute($data);
    }

    public function updateSupplier(int $supplierId, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE shop_supplier SET
                supplier_name = :supplier_name,
                supplier_mobile = :supplier_mobile,
                supplier_address = :supplier_address
             WHERE supplierid = :supplierid",
        );

        return $stmt->execute([
            "supplierid" => $supplierId,
            "supplier_name" => $data["supplier_name"],
            "supplier_mobile" => $data["supplier_mobile"],
            "supplier_address" => $data["supplier_address"],
        ]);
    }
}
