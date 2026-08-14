<?php

declare(strict_types=1);

use Core\Seeders\Seeder;

/**
 * PrivilegeSeeder
 *
 * Seeds the default privilege groups and their functional mappings.
 *
 * Privilege IDs:
 *   1 — Admin (System Administrator)
 *   2 — Cashier (Default Cashier)
 *   3 — Manager (Store Manager)
 */
class PrivilegeSeeder extends Seeder
{
    public function run(): void
    {
        $this->info("Seeding privileges…");

        $groups = [
            [
                'id' => 1,
                'privilegename' => 'System Administrator',
                'status' => 1
            ],
            [
                'id' => 2,
                'privilegename' => 'Default Cashier',
                'status' => 1
            ],
            [
                'id' => 3,
                'privilegename' => 'Store Manager',
                'status' => 1
            ]
        ];

        foreach ($groups as $group) {
            if ($this->exists('sys_privilege', 'id', $group['id'])) {
                continue;
            }

            $stmt = $this->db->prepare("
                INSERT INTO sys_privilege (id, privilegename, status)
                VALUES (:id, :privilegename, :status)
            ");
            $stmt->execute($group);
            $this->info("Created Privilege Group: {$group['privilegename']}");
        }

        $this->info("Mapping permissions…");

        // Admin maps (All permissions)
        $adminPermissions = array_merge(
            array_keys(PermissionCatalog::functionPermissions()),
            array_keys(PermissionCatalog::reportPermissions())
        );

        $this->seedGroupMap(1, $adminPermissions);

        // Manager maps
        $managerPermissions = [
            // Dashboard, POS, Items, Categories, Suppliers, Customers, Stocks, Reports View
            "p_1", "p_2", "p_14", "p_15", "p_16", "p_17", "p_18", "p_19", "p_20", "p_21", 
            "p_22", "p_23", "p_24", "p_25", "p_26", "p_27", "p_28", "p_29", "p_30", "p_31", 
            "p_32", "p_33", "p_34", "p_35", "p_36", "p_37", "p_38", "p_39", "p_40", "p_41", 
            "p_42", "p_43", "p_45", "p_46", "p_47", "p_48", "p_49", "p_50", "p_51", "p_52", 
            "p_53", "p_54", "p_55", "p_56", "p_57", "p_58", "p_59", "p_60", "p_61", "p_62",
            "r_1", "r_2", "r_3", "r_5", "r_6", "r_7", "r_8", "r_9", "r_10", "r_11", "r_12", 
            "r_13", "r_14", "r_15", "r_16", "r_17", "r_18", "r_19", "r_20", "r_21", "r_24", 
            "r_25", "r_26", "r_27", "r_28", "r_29", "r_30", "r_31", "r_32"
        ];
        $this->seedGroupMap(3, $managerPermissions);

        // Cashier maps
        $cashierPermissions = [
            // Dashboard, POS, Cashier actions
            "p_1", "p_2", "p_56", "p_57", "p_58", "p_59", "p_60", "p_61"
        ];
        $this->seedGroupMap(2, $cashierPermissions);

        $this->info("Done.");
    }

    private function seedGroupMap(int $mapId, array $permissionKeys): void
    {
        // Clean old mappings for this group
        $stmt = $this->db->prepare("DELETE FROM sys_privilegemap WHERE mapid = :mapid");
        $stmt->execute(['mapid' => $mapId]);

        $insert = $this->db->prepare("
            INSERT INTO sys_privilegemap (mapid, linkdata, linkvalue)
            VALUES (:mapid, :linkdata, '1')
        ");

        foreach ($permissionKeys as $key) {
            $insert->execute([
                'mapid' => $mapId,
                'linkdata' => $key
            ]);
        }
        $this->info("Mapped " . count($permissionKeys) . " permissions for group ID {$mapId}.");
    }
}
