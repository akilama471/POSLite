<?php

declare(strict_types=1);

class SupplierController
{
    public function index(Request $request): void
    {
        $supplierModel = new Supplier();

        View::make("catalog/suppliers/index", [
            "title" => "Suppliers",
            "auth" => auth_user(),
            "suppliers" => $supplierModel->allOrdered(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function create(Request $request): void
    {
        View::make("catalog/suppliers/create", [
            "title" => "Add Supplier",
            "auth" => auth_user(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/suppliers/create");
        }

        $supplierModel = new Supplier();
        $name = trim((string) $request->input("supplier_name", ""));
        $address = trim((string) $request->input("supplier_address", ""));
        $mobile = trim((string) $request->input("supplier_mobile", ""));

        if (strlen($name) < 2 || strlen($address) < 5 || strlen($mobile) < 9) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Supplier name, address, and a valid mobile number are required."];
            redirect("/suppliers/create");
        }

        if ($supplierModel->existsByName($name)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That supplier is already registered."];
            redirect("/suppliers/create");
        }

        $supplierModel->createSupplier([
            "supplier_name" => $name,
            "supplier_mobile" => $mobile,
            "supplier_address" => $address,
            "eff_date" => date("Y-m-d H:i:s"),
        ]);

        $_SESSION["flash"] = ["type" => "success", "message" => "Supplier created successfully."];
        redirect("/suppliers");
    }

    public function edit(Request $request, string $id): void
    {
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findById((int) $id);

        if ($supplier === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Supplier not found."];
            redirect("/suppliers");
        }

        View::make("catalog/suppliers/edit", [
            "title" => "Edit Supplier",
            "auth" => auth_user(),
            "supplier" => $supplier,
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/suppliers/" . (int) $id . "/edit");
        }

        $supplierId = (int) $id;
        $supplierModel = new Supplier();

        if ($supplierModel->findById($supplierId) === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Supplier not found."];
            redirect("/suppliers");
        }

        $name = trim((string) $request->input("supplier_name", ""));
        $address = trim((string) $request->input("supplier_address", ""));
        $mobile = trim((string) $request->input("supplier_mobile", ""));

        if (strlen($name) < 2 || strlen($address) < 3 || strlen($mobile) < 9) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Supplier name, address, and mobile number are required."];
            redirect("/suppliers/" . $supplierId . "/edit");
        }

        $supplierModel->updateSupplier($supplierId, [
            "supplier_name" => $name,
            "supplier_address" => $address,
            "supplier_mobile" => $mobile,
        ]);

        $_SESSION["flash"] = ["type" => "success", "message" => "Supplier updated successfully."];
        redirect("/suppliers");
    }
}
