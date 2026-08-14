<?php

declare(strict_types=1);

class SettingsController
{
    public function index(Request $request): void
    {
        View::make("settings/index", [
            "title" => "System Settings",
            "auth" => auth_user(),
            "sections" => $this->sections(),
        ]);
    }

    private function sections(): array
    {
        $sections = [
            [
                "title" => "User Management",
                "permission" => "p_63",
                "items" => [
                    ["label" => "Add User", "href" => "/settings/users/create", "permission" => "p_64"],
                    ["label" => "Manage Users", "href" => "/settings/users", "permission" => "p_65"],
                ],
            ],
            [
                "title" => "Permission Management",
                "permission" => "p_66",
                "items" => [
                    ["label" => "Function Permission", "href" => "/settings/privileges", "permission" => "p_67"],
                    ["label" => "User Function Mapping", "href" => "/settings/user-privileges", "permission" => "p_68"],
                ],
            ],
            [
                "title" => "Shop Management",
                "permission" => "p_69",
                "items" => [
                    ["label" => "Add Shop", "href" => "/settings/shops/create", "permission" => "p_70"],
                    ["label" => "Shop List", "href" => "/settings/shops", "permission" => "p_71"],
                ],
            ],
        ];

        $auth = auth_user();
        if (($auth["user_role"] ?? "") === "admin") {
            $sections[] = [
                "title" => "Company Management",
                "permission" => null,
                "items" => [
                    ["label" => "Add Company", "href" => "/settings/companies/create", "permission" => null],
                    ["label" => "Company List", "href" => "/settings/companies", "permission" => null],
                ],
            ];
        }

        $sections[] = [
            "title" => "My Account",
            "permission" => null,
            "items" => [
                ["label" => "Profile and Password", "href" => "/settings/profile", "permission" => null],
            ],
        ];

        return $sections;
    }
}
