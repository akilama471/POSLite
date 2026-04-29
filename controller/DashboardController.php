<?php

declare(strict_types=1);

class DashboardController
{
    public function index(Request $request): void
    {
        $dashboardModel = new Dashboard();
        $auth = $_SESSION["auth"];
        $userId = (int) ($auth["user_id"] ?? 0);
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $permissions = $auth["permissions"] ?? [];

        View::make("dashboard/index", [
            "title" => "Dashboard",
            "auth" => $auth,
            "permissions" => $permissions,
            "summary" => $dashboardModel->summary($userId, $shopId),
            "salesTrend" => $dashboardModel->salesTrend($userId, $shopId),
            "topItems" => $dashboardModel->topItems($userId, $shopId),
            "topPhones" => $dashboardModel->topPhones($userId, $shopId),
            "topCards" => $dashboardModel->topCards($userId, $shopId),
            "menu" => $this->menuConfig($permissions),
        ]);
    }

    private function menuConfig(array $permissions): array
    {
        $itemHref = ($permissions["p_16"] ?? false)
            ? "/items"
            : (($permissions["p_17"] ?? false)
                ? "/items/search"
                : (($permissions["p_19"] ?? false)
                    ? "/item-colors"
                    : (($permissions["p_52"] ?? false)
                        ? "/item-alerts"
                        : (($permissions["p_15"] ?? false) ? "/items/create" : "#"))));
        $supplierHref = ($permissions["p_26"] ?? false)
            ? "/suppliers"
            : (($permissions["p_27"] ?? false)
                ? "/supplier-accounts"
                : (($permissions["p_28"] ?? false)
                    ? "/supplier-credit-balances"
                    : (($permissions["p_29"] ?? false)
                    ? "/supplier-payments"
                    : (($permissions["p_25"] ?? false) ? "/suppliers/create" : "#"))));
        $customerHref = ($permissions["p_37"] ?? false)
            ? "/customers"
            : (($permissions["p_39"] ?? false)
                ? "/customer-accounts"
                : (($permissions["p_40"] ?? false)
                    ? "/customer-credit-balances"
                    : (($permissions["p_41"] ?? false)
                    ? "/customer-payments"
                    : (($permissions["p_36"] ?? false) ? "/customers/create" : "#"))));
        $reportsHref = ($permissions["r_15"] ?? false)
            ? "/reports/supplier-payments"
            : (($permissions["r_19"] ?? false) ? "/reports/customer-payments" : "#");
        $cashierHref = (($permissions["p_59"] ?? false) || ($permissions["p_58"] ?? false))
            ? "/cashier"
            : "#";

        return [
            ["key" => "p_1", "label" => "Dashboard", "href" => "/dashboard", "migrated" => true],
            ["key" => "p_2", "label" => "Point Of Sale", "href" => "#", "migrated" => false],
            ["key" => "p_3", "label" => "Repair Job", "href" => "#", "migrated" => false],
            ["key" => "p_14", "label" => "Shop Items", "href" => $itemHref, "migrated" => $itemHref !== "#"],
            ["key" => "p_18", "label" => "Manage Category", "href" => "/categories", "migrated" => true],
            ["key" => "p_24", "label" => "Suppliers", "href" => $supplierHref, "migrated" => $supplierHref !== "#"],
            ["key" => "p_30", "label" => "Bill Details", "href" => "#", "migrated" => false],
            ["key" => "p_35", "label" => "Customers", "href" => $customerHref, "migrated" => $customerHref !== "#"],
            ["key" => "p_42", "label" => "Purchases", "href" => "#", "migrated" => false],
            ["key" => "p_47", "label" => "Stocks", "href" => "#", "migrated" => false],
            ["key" => "p_56", "label" => "Cashier", "href" => $cashierHref, "migrated" => $cashierHref !== "#"],
            ["key" => "p_62", "label" => "Reports", "href" => $reportsHref, "migrated" => $reportsHref !== "#"],
            ["key" => "p_77", "label" => "SMS Broadcast", "href" => "#", "migrated" => false],
            ["key" => "p_63", "label" => "System Settings", "href" => "/settings", "migrated" => true],
        ];
    }
}
