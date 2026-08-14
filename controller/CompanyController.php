<?php

declare(strict_types=1);

class CompanyController
{
    private function checkAdmin(): void
    {
        $auth = auth_user();
        if (($auth["user_role"] ?? "") !== "admin") {
            http_response_code(403);
            View::make("errors/403", [
                "title" => "Forbidden",
                "message" => "You do not have permission to access company settings. Only system administrators are allowed.",
            ]);
            exit();
        }
    }

    public function index(Request $request): void
    {
        $this->checkAdmin();

        $companyModel = new Company();
        $companies = $companyModel->listAll();

        View::make("settings/companies/index", [
            "title" => "Manage Companies",
            "auth" => auth_user(),
            "companies" => $companies,
            "flash" => flash_consume(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->checkAdmin();

        View::make("settings/companies/create", [
            "title" => "Add Company",
            "auth" => auth_user(),
            "flash" => flash_consume(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->checkAdmin();

        if (!verify_csrf((string) $request->input("_token"))) {
            flash("error", "Your session expired. Please try again.");
            redirect("/settings/companies/create");
        }

        $name = trim((string) $request->input("company_name", ""));
        $address = trim((string) $request->input("company_address", ""));
        $phone = trim((string) $request->input("company_phone", ""));
        $email = trim((string) $request->input("company_email", ""));

        if ($name === "") {
            flash("error", "Company Name is required.");
            redirect("/settings/companies/create");
        }

        $companyModel = new Company();

        if ($companyModel->existsByName($name)) {
            flash("error", "A company with that name is already registered.");
            redirect("/settings/companies/create");
        }

        $success = $companyModel->createCompany([
            "company_name" => $name,
            "company_address" => $address === "" ? null : $address,
            "company_phone" => $phone === "" ? null : $phone,
            "company_email" => $email === "" ? null : $email,
        ]);

        if ($success) {
            flash("success", "Company successfully created.");
            redirect("/settings/companies");
        } else {
            flash("error", "Failed to create company. Please try again.");
            redirect("/settings/companies/create");
        }
    }
}
