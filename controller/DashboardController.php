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
            "menu" => $this->menuConfig(),
        ]);
    }

    private function menuConfig(): array
    {
        return [
            ["key" => "p_1", "label" => "Dashboard", "href" => "/dashboard", "migrated" => true],
            ["key" => "p_2", "label" => "Point Of Sale", "href" => "#", "migrated" => false],
            ["key" => "p_3", "label" => "Repair Job", "href" => "#", "migrated" => false],
            ["key" => "p_14", "label" => "Shop Items", "href" => "#", "migrated" => false],
            ["key" => "p_24", "label" => "Suppliers", "href" => "#", "migrated" => false],
            ["key" => "p_30", "label" => "Bill Details", "href" => "#", "migrated" => false],
            ["key" => "p_35", "label" => "Customers", "href" => "#", "migrated" => false],
            ["key" => "p_42", "label" => "Purchases", "href" => "#", "migrated" => false],
            ["key" => "p_47", "label" => "Stocks", "href" => "#", "migrated" => false],
            ["key" => "p_56", "label" => "Cashier", "href" => "#", "migrated" => false],
            ["key" => "p_62", "label" => "Reports", "href" => "#", "migrated" => false],
            ["key" => "p_77", "label" => "SMS Broadcast", "href" => "#", "migrated" => false],
        ];
    }
}
