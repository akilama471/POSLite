<?php

declare(strict_types=1);

class CustomerController
{
    public function index(Request $request): void
    {
        $customerModel = new Customer();
        $searchName = trim((string) $request->input("name", ""));
        $searchMobile = trim((string) $request->input("mobile", ""));

        View::make("catalog/customers/index", [
            "title" => "Customers",
            "auth" => auth_user(),
            "customers" => $customerModel->search($searchName, $searchMobile),
            "filters" => [
                "name" => $searchName,
                "mobile" => $searchMobile,
            ],
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function create(Request $request): void
    {
        View::make("catalog/customers/create", [
            "title" => "Add Customer",
            "auth" => auth_user(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/customers/create");
        }

        $customerModel = new Customer();
        $name = trim((string) $request->input("cus_name", ""));
        $mobile = trim((string) $request->input("cus_mobile", ""));

        if (strlen($name) < 2 || strlen($mobile) < 9) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Customer name and a valid mobile number are required."];
            redirect("/customers/create");
        }

        if ($customerModel->existsByName($name)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That customer is already registered."];
            redirect("/customers/create");
        }

        $customerModel->createCustomer([
            "cus_name" => $name,
            "cus_addr" => trim((string) $request->input("cus_addr", "")),
            "cus_nic" => trim((string) $request->input("cus_nic", "")),
            "cus_emai" => trim((string) $request->input("cus_emai", "")),
            "cus_mobile" => $mobile,
            "cus_tpno" => trim((string) $request->input("cus_tpno", "")),
            "cus_bday" => trim((string) $request->input("cus_bday", "")),
            "cus_remark" => trim((string) $request->input("cus_remark", "")),
            "add_time" => date("Y-m-d H:i:s"),
        ]);

        $_SESSION["flash"] = ["type" => "success", "message" => "Customer created successfully."];
        redirect("/customers");
    }

    public function edit(Request $request, string $id): void
    {
        $customerModel = new Customer();
        $customer = $customerModel->findById((int) $id);

        if ($customer === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Customer not found."];
            redirect("/customers");
        }

        View::make("catalog/customers/edit", [
            "title" => "Edit Customer",
            "auth" => auth_user(),
            "customer" => $customer,
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/customers/" . (int) $id . "/edit");
        }

        $customerId = (int) $id;
        $customerModel = new Customer();

        if ($customerModel->findById($customerId) === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Customer not found."];
            redirect("/customers");
        }

        $name = trim((string) $request->input("cus_name", ""));
        $mobile = trim((string) $request->input("cus_mobile", ""));

        if (strlen($name) < 2 || strlen($mobile) < 9) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Customer name and mobile number are required."];
            redirect("/customers/" . $customerId . "/edit");
        }

        $customerModel->updateCustomer($customerId, [
            "cus_name" => $name,
            "cus_addr" => trim((string) $request->input("cus_addr", "")),
            "cus_nic" => trim((string) $request->input("cus_nic", "")),
            "cus_emai" => trim((string) $request->input("cus_emai", "")),
            "cus_mobile" => $mobile,
            "cus_tpno" => trim((string) $request->input("cus_tpno", "")),
            "cus_bday" => trim((string) $request->input("cus_bday", "")),
            "cus_remark" => trim((string) $request->input("cus_remark", "")),
        ]);

        $_SESSION["flash"] = ["type" => "success", "message" => "Customer updated successfully."];
        redirect("/customers");
    }

    public function updateStatus(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/customers");
        }

        $customerId = (int) $id;
        $action = (string) $request->input("action", "");
        $status = $action === "recover" ? 1 : 2;

        $customerModel = new Customer();
        if ($customerModel->findById($customerId) === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Customer not found."];
            redirect("/customers");
        }

        $customerModel->updateStatus($customerId, $status);

        $_SESSION["flash"] = [
            "type" => "success",
            "message" => $status === 1 ? "Customer recovered successfully." : "Customer deleted successfully.",
        ];
        redirect("/customers");
    }
}
