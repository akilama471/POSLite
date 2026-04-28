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
        return [
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
                "title" => "My Account",
                "permission" => null,
                "items" => [
                    ["label" => "Profile and Password", "href" => "/settings/profile", "permission" => null],
                ],
            ],
        ];
    }
}
