<?php

declare(strict_types=1);

class Shop extends Model
{
    protected $table = "sys_shop";

    private function mapLegacyFields(?array $shop): ?array
    {
        if ($shop === null) {
            return null;
        }

        // Map database columns (new schema) to legacy columns (old schema)
        if (isset($shop['id']) && !isset($shop['shopid'])) {
            $shop['shopid'] = $shop['id'];
        } elseif (isset($shop['shopid']) && !isset($shop['id'])) {
            $shop['id'] = $shop['shopid'];
        }

        if (isset($shop['shop_name'])) {
            $shop['shopname'] = $shop['shop_name'];
            $shop['shop_info_name'] = $shop['shop_name'];
        }

        if (isset($shop['shop_code'])) {
            $shop['pos_uniq'] = $shop['shop_code'];
        }

        // Add dummy values for other legacy columns so PHP doesn't complain about undefined keys
        $legacyKeys = [
            'shopaddress' => '',
            'shop_tel_1' => '',
            'shop_fax' => '',
            'shopemail' => '',
            'bill_foot_1' => '',
            'bill_foot_2' => '',
            'img_path' => '',
            'lang' => 1,
            'addeddate' => date('Y-m-d H:i:s')
        ];

        foreach ($legacyKeys as $key => $default) {
            if (!isset($shop[$key])) {
                $shop[$key] = $default;
            }
        }

        return $shop;
    }

    public function findByShopId(int $shopId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sys_shop WHERE id = :shopid LIMIT 1");
        $stmt->execute(["shopid" => $shopId]);

        $shop = $stmt->fetch();
        return $this->mapLegacyFields($shop ?: null);
    }

    public function activeShops(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM sys_shop WHERE status < 2 AND id > 0 ORDER BY id ASC",
        );

        $shops = $stmt->fetchAll();
        return array_map([$this, 'mapLegacyFields'], $shops);
    }

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM sys_shop WHERE id > 0 ORDER BY id ASC",
        );

        $shops = $stmt->fetchAll();
        return array_map([$this, 'mapLegacyFields'], $shops);
    }

    public function nextShopId(): int
    {
        $stmt = $this->db->query("SELECT COALESCE(MAX(id), 0) FROM sys_shop");
        return ((int) $stmt->fetchColumn()) + 1;
    }

    public function existsByName(string $name): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM sys_shop WHERE shop_name = :name",
        );
        $stmt->execute(["name" => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createShop(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sys_shop
            (shop_name, shop_code, status)
            VALUES
            (:shop_name, :shop_code, 1)",
        );

        return $stmt->execute([
            "shop_name" => $data["shop_info_name"] ?? $data["shopname"] ?? "Shop",
            "shop_code" => $data["pos_uniq"] ?? $data["shop_code"] ?? "",
        ]);
    }

    public function updateShop(int $shopId, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_shop SET
                shop_name = :shop_name
             WHERE id = :shopid",
        );

        return $stmt->execute([
            "shopid" => $shopId,
            "shop_name" => $data["shop_info_name"] ?? $data["shopname"] ?? "",
        ]);
    }
}
