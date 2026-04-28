<?php

declare(strict_types=1);

class Shop extends Model
{
    protected $table = "sys_shop";

    public function findByShopId(int $shopId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sys_shop WHERE shopid = :shopid LIMIT 1");
        $stmt->execute(["shopid" => $shopId]);

        $shop = $stmt->fetch();
        return $shop ?: null;
    }

    public function activeShops(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM sys_shop WHERE status < 2 AND shopid > 0 ORDER BY shopid ASC",
        );

        return $stmt->fetchAll();
    }

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM sys_shop WHERE shopid > 0 ORDER BY shopid ASC",
        );

        return $stmt->fetchAll();
    }

    public function nextShopId(): int
    {
        $stmt = $this->db->query("SELECT COALESCE(MAX(shopid), 0) FROM sys_shop");
        return ((int) $stmt->fetchColumn()) + 1;
    }

    public function existsByName(string $name): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM sys_shop WHERE shopname = :name",
        );
        $stmt->execute(["name" => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createShop(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sys_shop
            (shopid, shopname, shop_info_name, shopaddress, shop_tel_1, shop_fax, shopemail, status, lang, img_path, bill_foot_1, bill_foot_2, pos_uniq, addeddate)
            VALUES
            (:shopid, :shopname, :shop_info_name, :shopaddress, :shop_tel_1, :shop_fax, :shopemail, 1, 1, '', :bill_foot_1, :bill_foot_2, :pos_uniq, :addeddate)",
        );

        return $stmt->execute($data);
    }

    public function updateShop(int $shopId, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_shop SET
                shopname = :shopname,
                shop_info_name = :shop_info_name,
                shopaddress = :shopaddress,
                shop_tel_1 = :shop_tel_1,
                shop_fax = :shop_fax,
                shopemail = :shopemail,
                bill_foot_1 = :bill_foot_1,
                bill_foot_2 = :bill_foot_2
             WHERE shopid = :shopid",
        );

        return $stmt->execute([
            "shopid" => $shopId,
            "shopname" => $data["shopname"],
            "shop_info_name" => $data["shop_info_name"],
            "shopaddress" => $data["shopaddress"],
            "shop_tel_1" => $data["shop_tel_1"],
            "shop_fax" => $data["shop_fax"],
            "shopemail" => $data["shopemail"],
            "bill_foot_1" => $data["bill_foot_1"],
            "bill_foot_2" => $data["bill_foot_2"],
        ]);
    }
}
