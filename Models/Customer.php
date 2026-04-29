<?php

declare(strict_types=1);

class Customer extends Model
{
    protected $table = "shop_customer";

    public function search(string $name = "", string $mobile = ""): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM shop_customer
             WHERE cus_name LIKE :name
               AND cus_mobile LIKE :mobile
               AND recordid > 0
             ORDER BY recordid ASC",
        );
        $stmt->execute([
            "name" => "%" . $name . "%",
            "mobile" => "%" . $mobile . "%",
        ]);

        return $stmt->fetchAll();
    }

    public function findById(int $customerId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM shop_customer WHERE recordid = :recordid LIMIT 1",
        );
        $stmt->execute(["recordid" => $customerId]);

        $customer = $stmt->fetch();
        return $customer ?: null;
    }

    public function existsByName(string $name): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM shop_customer WHERE cus_name = :name",
        );
        $stmt->execute(["name" => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createCustomer(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO shop_customer
            (cus_name, cus_addr, cus_nic, cus_emai, cus_mobile, cus_tpno, cus_bday, cus_remark, status, add_time)
            VALUES
            (:cus_name, :cus_addr, :cus_nic, :cus_emai, :cus_mobile, :cus_tpno, :cus_bday, :cus_remark, 1, :add_time)",
        );

        return $stmt->execute($data);
    }

    public function updateCustomer(int $customerId, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE shop_customer SET
                cus_name = :cus_name,
                cus_addr = :cus_addr,
                cus_nic = :cus_nic,
                cus_emai = :cus_emai,
                cus_mobile = :cus_mobile,
                cus_tpno = :cus_tpno,
                cus_bday = :cus_bday,
                cus_remark = :cus_remark
             WHERE recordid = :recordid",
        );

        return $stmt->execute([
            "recordid" => $customerId,
            "cus_name" => $data["cus_name"],
            "cus_addr" => $data["cus_addr"],
            "cus_nic" => $data["cus_nic"],
            "cus_emai" => $data["cus_emai"],
            "cus_mobile" => $data["cus_mobile"],
            "cus_tpno" => $data["cus_tpno"],
            "cus_bday" => $data["cus_bday"],
            "cus_remark" => $data["cus_remark"],
        ]);
    }

    public function updateStatus(int $customerId, int $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE shop_customer SET status = :status WHERE recordid = :recordid",
        );

        return $stmt->execute([
            "recordid" => $customerId,
            "status" => $status,
        ]);
    }
}
