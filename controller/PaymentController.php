<?php

declare(strict_types=1);

class PaymentController
{
    public function supplierForm(Request $request): void
    {
        $supplierModel = new Supplier();
        $selectedId = (int) $request->input("supplier", 0);
        $selectedSupplier = $selectedId > 0 ? $supplierModel->findById($selectedId) : null;

        View::make("finance/suppliers/form", [
            "title" => "Supplier Payment",
            "auth" => auth_user(),
            "suppliers" => $supplierModel->allOrdered(),
            "selectedSupplier" => $selectedSupplier,
            "flash" => $_SESSION["flash"] ?? null,
            "receipt" => $_SESSION["payment_receipt"] ?? null,
        ]);

        unset($_SESSION["flash"], $_SESSION["payment_receipt"]);
    }

    public function customerForm(Request $request): void
    {
        $customerModel = new Customer();
        $selectedId = (int) $request->input("customer", 0);
        $selectedCustomer = $selectedId > 0 ? $customerModel->findById($selectedId) : null;

        View::make("finance/customers/form", [
            "title" => "Customer Payment",
            "auth" => auth_user(),
            "customers" => $customerModel->search("", ""),
            "selectedCustomer" => $selectedCustomer,
            "flash" => $_SESSION["flash"] ?? null,
            "receipt" => $_SESSION["payment_receipt"] ?? null,
        ]);

        unset($_SESSION["flash"], $_SESSION["payment_receipt"]);
    }

    public function supplierDetails(Request $request): void
    {
        $supplierId = (int) $request->input("supplier_id", 0);
        $supplierModel = new Supplier();
        $financeModel = new Finance();
        $supplier = $supplierModel->findById($supplierId);

        if ($supplier === null) {
            json_response(["found" => false], 404);
        }

        json_response([
            "found" => true,
            "id" => (int) $supplier["supplierid"],
            "name" => (string) $supplier["supplier_name"],
            "address" => (string) ($supplier["supplier_address"] ?? ""),
            "mobile" => (string) ($supplier["supplier_mobile"] ?? ""),
            "balance" => (float) ($supplier["accbalance"] ?? 0),
            "credits" => $financeModel->supplierCredits((int) $supplier["supplierid"]),
        ]);
    }

    public function customerDetails(Request $request): void
    {
        $customerId = (int) $request->input("customer_id", 0);
        $customerModel = new Customer();
        $financeModel = new Finance();
        $customer = $customerModel->findById($customerId);

        if ($customer === null) {
            json_response(["found" => false], 404);
        }

        json_response([
            "found" => true,
            "id" => (int) $customer["recordid"],
            "name" => (string) $customer["cus_name"],
            "address" => (string) ($customer["cus_addr"] ?? ""),
            "mobile" => (string) ($customer["cus_mobile"] ?? ""),
            "balance" => (float) ($customer["accbalance"] ?? 0),
            "credits" => $financeModel->customerCredits((int) $customer["recordid"]),
        ]);
    }

    public function storeSupplierPayment(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/supplier-payments");
        }

        $supplierId = (int) $request->input("supplier_id", 0);
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findById($supplierId);

        if ($supplier === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Supplier not found."];
            redirect("/supplier-payments");
        }

        $method = (string) $request->input("payment_method", "");
        $finance = new Finance();
        $payload = $this->buildSupplierPayload($request, $supplier);

        if ($payload === null) {
            redirect("/supplier-payments?supplier=" . $supplierId);
        }

        $_SESSION["payment_receipt"] = $finance->createSupplierPayment($payload);
        $_SESSION["flash"] = ["type" => "success", "message" => "Supplier payment recorded successfully."];

        redirect("/supplier-payments?supplier=" . $supplierId);
    }

    public function storeCustomerPayment(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/customer-payments");
        }

        $customerId = (int) $request->input("customer_id", 0);
        $customerModel = new Customer();
        $customer = $customerModel->findById($customerId);

        if ($customer === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Customer not found."];
            redirect("/customer-payments");
        }

        $finance = new Finance();
        $payload = $this->buildCustomerPayload($request, $customer);

        if ($payload === null) {
            redirect("/customer-payments?customer=" . $customerId);
        }

        $_SESSION["payment_receipt"] = $finance->createCustomerPayment($payload);
        $_SESSION["flash"] = ["type" => "success", "message" => "Customer payment recorded successfully."];

        redirect("/customer-payments?customer=" . $customerId);
    }

    private function buildSupplierPayload(Request $request, array $supplier): ?array
    {
        $method = (string) $request->input("payment_method", "");
        $auth = auth_user() ?? [];
        $base = [
            "supplier_id" => (int) $supplier["supplierid"],
            "name" => (string) $supplier["supplier_name"],
            "shop_id" => (int) ($auth["shop_id"] ?? 0),
            "user_id" => (int) ($auth["user_id"] ?? 0),
            "recordtime" => date("Y-m-d H:i:s"),
        ];

        if ($method === "cash") {
            $amount = (float) $request->input("pay_cashamount", 0);
            $reason = trim((string) $request->input("pay_cashreason", ""));

            if ($amount <= 0 || $reason === "") {
                $_SESSION["flash"] = ["type" => "error", "message" => "Cash amount and reason are required."];
                return null;
            }

            return $base + [
                "method" => "cash",
                "amount" => $amount,
                "reason" => $reason,
            ];
        }

        if ($method === "cheque") {
            $amount = (float) $request->input("pay_chequeamount", 0);
            $number = trim((string) $request->input("pay_chequenumber", ""));
            $reason = trim((string) $request->input("pay_chequereason", ""));

            if ($amount <= 0 || $number === "" || $reason === "") {
                $_SESSION["flash"] = ["type" => "error", "message" => "Cheque amount, number, and reason are required."];
                return null;
            }

            return $base + [
                "method" => "cheque",
                "amount" => $amount,
                "reason" => $reason,
                "cheque_number" => $number,
                "cheque_date" => (string) $request->input("pay_chequedate", ""),
                "reminder" => (int) $request->input("cheque_reminder", 0),
                "reminder_date" => (string) $request->input("pay_chequereminderdate", ""),
            ];
        }

        $credits = $request->input("cash_credits_rec", []);
        $credits = is_array($credits) ? array_map("intval", $credits) : [];

        if ($credits === []) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Select at least one cash credit record to settle."];
            return null;
        }

        return $base + [
            "method" => "credit",
            "credit_ids" => $credits,
        ];
    }

    private function buildCustomerPayload(Request $request, array $customer): ?array
    {
        $method = (string) $request->input("payment_method", "");
        $auth = auth_user() ?? [];
        $base = [
            "customer_id" => (int) $customer["recordid"],
            "name" => (string) $customer["cus_name"],
            "shop_id" => (int) ($auth["shop_id"] ?? 0),
            "user_id" => (int) ($auth["user_id"] ?? 0),
            "recordtime" => date("Y-m-d H:i:s"),
        ];

        if ($method === "cash") {
            $amount = (float) $request->input("pay_cashamount", 0);
            $reason = trim((string) $request->input("pay_cashreason", ""));

            if ($amount <= 0 || $reason === "") {
                $_SESSION["flash"] = ["type" => "error", "message" => "Cash amount and reason are required."];
                return null;
            }

            return $base + [
                "method" => "cash",
                "amount" => $amount,
                "reason" => $reason,
            ];
        }

        if ($method === "cheque") {
            $amount = (float) $request->input("pay_chequeamount", 0);
            $number = trim((string) $request->input("pay_chequenumber", ""));
            $reason = trim((string) $request->input("pay_chequereason", ""));

            if ($amount <= 0 || $number === "" || $reason === "") {
                $_SESSION["flash"] = ["type" => "error", "message" => "Cheque amount, number, and reason are required."];
                return null;
            }

            return $base + [
                "method" => "cheque",
                "amount" => $amount,
                "reason" => $reason,
                "cheque_number" => $number,
                "cheque_date" => (string) $request->input("pay_chequedate", ""),
                "reminder" => (int) $request->input("cheque_reminder", 0),
                "reminder_date" => (string) $request->input("pay_chequereminderdate", ""),
            ];
        }

        $credits = $request->input("cash_credits_rec", []);
        $credits = is_array($credits) ? array_map("intval", $credits) : [];

        if ($credits === []) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Select at least one cash credit record to settle."];
            return null;
        }

        return $base + [
            "method" => "credit",
            "credit_ids" => $credits,
        ];
    }
}
