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
}
