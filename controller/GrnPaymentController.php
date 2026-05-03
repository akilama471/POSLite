<?php

declare(strict_types=1);

class GrnPaymentController
{
    public function index(Request $request): void
    {
        $auth = auth_user() ?? [];
        $authShopId = (int) ($auth["shop_id"] ?? 0);
        $shopModel = new Shop();
        $supplierModel = new Supplier();
        $finance = new Finance();

        $filters = [
            "shop_id" => $authShopId > 0 ? $authShopId : (int) $request->input("shop_id", 0),
            "supplier_id" => (int) $request->input("supplier_id", 0),
            "grn_refno" => trim((string) $request->input("grn_refno", "")),
            "payment_status" => trim((string) $request->input("payment_status", "due")),
            "from_date" => trim((string) $request->input("from_date", date("Y-m-d", strtotime("-1 month")))),
            "to_date" => trim((string) $request->input("to_date", "")),
        ];

        View::make("finance/grns/index", [
            "title" => "GRN Payment",
            "auth" => $auth,
            "filters" => $filters,
            "shops" => $authShopId > 0
                ? array_filter($shopModel->allOrdered(), static fn (array $shop): bool => (int) ($shop["shopid"] ?? 0) === $authShopId)
                : $shopModel->allOrdered(),
            "suppliers" => $supplierModel->allOrdered(),
            "results" => $finance->searchGrnPayments($filters, $authShopId),
            "flash" => $_SESSION["flash"] ?? null,
            "receipt" => $_SESSION["grn_payment_receipt"] ?? null,
        ]);

        unset($_SESSION["flash"], $_SESSION["grn_payment_receipt"]);
    }

    public function show(Request $request, string $recordId): void
    {
        $auth = auth_user() ?? [];
        $authShopId = (int) ($auth["shop_id"] ?? 0);
        $finance = new Finance();
        $payment = $finance->findGrnPaymentById((int) $recordId, $authShopId);

        if ($payment === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "GRN payment record not found."];
            redirect("/grn-payments");
        }

        View::make("finance/grns/show", [
            "title" => "GRN Payment Detail",
            "auth" => $auth,
            "payment" => $payment,
            "history" => $finance->grnPaymentHistory((string) ($payment["grn_refno"] ?? ""), $authShopId),
            "credits" => $finance->supplierCredits((int) ($payment["supply_id"] ?? 0)),
            "flash" => $_SESSION["flash"] ?? null,
            "receipt" => $_SESSION["grn_payment_receipt"] ?? null,
        ]);

        unset($_SESSION["flash"], $_SESSION["grn_payment_receipt"]);
    }

    public function payCash(Request $request, string $recordId): void
    {
        if (!$this->validCsrf($request)) {
            redirect("/grn-payments/" . (int) $recordId);
        }

        $auth = auth_user() ?? [];
        $finance = new Finance();

        try {
            $_SESSION["grn_payment_receipt"] = $finance->settleGrnDueCash((int) $recordId, [
                "amount" => (float) $request->input("pay_cashamount", 0),
                "recordtime" => date("Y-m-d H:i:s"),
                "user_id" => (int) ($auth["user_id"] ?? 0),
            ], (int) ($auth["shop_id"] ?? 0));
            $_SESSION["flash"] = ["type" => "success", "message" => "GRN cash payment recorded successfully."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/grn-payments/" . (int) $recordId);
    }

    public function payCheque(Request $request, string $recordId): void
    {
        if (!$this->validCsrf($request)) {
            redirect("/grn-payments/" . (int) $recordId);
        }

        $auth = auth_user() ?? [];
        $finance = new Finance();

        try {
            $_SESSION["grn_payment_receipt"] = $finance->settleGrnDueCheque((int) $recordId, [
                "amount" => (float) $request->input("pay_chequeamount", 0),
                "cheque_number" => trim((string) $request->input("pay_chequenumber", "")),
                "cheque_date" => trim((string) $request->input("pay_chequedate", "")),
                "reminder" => (int) $request->input("cheque_reminder", 0),
                "reminder_date" => trim((string) $request->input("pay_chequereminderdate", "")),
                "recordtime" => date("Y-m-d H:i:s"),
                "user_id" => (int) ($auth["user_id"] ?? 0),
            ], (int) ($auth["shop_id"] ?? 0));
            $_SESSION["flash"] = ["type" => "success", "message" => "GRN cheque payment recorded successfully."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/grn-payments/" . (int) $recordId);
    }

    public function payCredit(Request $request, string $recordId): void
    {
        if (!$this->validCsrf($request)) {
            redirect("/grn-payments/" . (int) $recordId);
        }

        $auth = auth_user() ?? [];
        $finance = new Finance();
        $creditIds = $request->input("cash_credits_rec", []);
        $creditIds = is_array($creditIds) ? array_map("intval", $creditIds) : [];

        try {
            $_SESSION["grn_payment_receipt"] = $finance->settleGrnDueCredit((int) $recordId, [
                "credit_ids" => $creditIds,
                "recordtime" => date("Y-m-d H:i:s"),
                "user_id" => (int) ($auth["user_id"] ?? 0),
            ], (int) ($auth["shop_id"] ?? 0));
            $_SESSION["flash"] = ["type" => "success", "message" => "GRN cash-credit settlement recorded successfully."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/grn-payments/" . (int) $recordId);
    }

    private function validCsrf(Request $request): bool
    {
        if (verify_csrf((string) $request->input("_token"))) {
            return true;
        }

        $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
        return false;
    }
}
