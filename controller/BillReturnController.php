<?php

declare(strict_types=1);

class BillReturnController
{
    public function activity(Request $request, string $billNumber, string $alterTime): void
    {
        $auth = auth_user() ?? [];
        $returnModel = new BillReturn();
        $context = $returnModel->pendingEvent($billNumber, (int) $alterTime, (int) ($auth["shop_id"] ?? 0));

        if ($context === null) {
            http_response_code(404);
            View::make("errors/404", ["title" => "Return Activity Not Found"]);
            return;
        }

        View::make("pos/return_activity", [
            "title" => "Return Activity",
            "auth" => $auth,
            "event" => $context["event"],
            "bill" => $context["bill"],
            "items" => $context["items"],
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function create(Request $request, string $billNumber): void
    {
        $auth = auth_user() ?? [];
        $returnModel = new BillReturn();
        $context = $returnModel->billForReturn($billNumber, (int) ($auth["shop_id"] ?? 0));

        if ($context === null) {
            http_response_code(404);
            View::make("errors/404", ["title" => "Bill Return Not Found"]);
            return;
        }

        View::make("pos/return_create", [
            "title" => "Create Bill Return",
            "auth" => $auth,
            "bill" => $context["bill"],
            "lines" => $context["lines"],
            "hasPending" => $returnModel->hasPendingActivity($billNumber),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function history(Request $request, string $billNumber): void
    {
        $auth = auth_user() ?? [];
        $returnModel = new BillReturn();
        $saleModel = new PosSale();
        $history = $returnModel->historyForBill(
            $billNumber,
            (int) ($auth["shop_id"] ?? 0),
        );
        $receipt = $saleModel->receipt($billNumber);

        if ($receipt === null) {
            http_response_code(404);
            View::make("errors/404", ["title" => "Bill Return History Not Found"]);
            return;
        }

        View::make("pos/return_history", [
            "title" => "Bill Return History",
            "auth" => $auth,
            "bill" => $receipt["bill"],
            "history" => $history,
        ]);
    }

    public function pending(Request $request): void
    {
        $auth = auth_user() ?? [];
        $returnModel = new BillReturn();

        View::make("pos/return_pending", [
            "title" => "Pending Return Activities",
            "auth" => $auth,
            "items" => $returnModel->pendingActivities((int) ($auth["shop_id"] ?? 0)),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request, string $billNumber): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos/bills/" . urlencode($billNumber) . "/returns/create");
        }

        $auth = auth_user() ?? [];
        $returnModel = new BillReturn();
        $entries = $request->input("entries", []);

        if (!is_array($entries)) {
            $entries = [];
        }

        try {
            $returnModel->createReturnRequest(
                $billNumber,
                (int) ($auth["shop_id"] ?? 0),
                (int) ($auth["user_id"] ?? 0),
                trim((string) $request->input("alter_reason", "")),
                array_values($entries),
            );
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
            redirect("/pos/bills/" . urlencode($billNumber) . "/returns/create");
        }

        $_SESSION["flash"] = ["type" => "success", "message" => "Return request created and queued for follow-up activity."];
        redirect("/pos/returns/pending");
    }

    public function settle(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos/returns/pending");
        }

        $auth = auth_user() ?? [];
        $returnModel = new BillReturn();

        try {
            $returnModel->processReplacementSettlement((int) $id, (int) ($auth["shop_id"] ?? 0), (int) ($auth["user_id"] ?? 0), [
                "replacement_type" => (int) $request->input("replacement_type", 0),
                "replacement_row_id" => (int) $request->input("replacement_row_id", 0),
                "replacement_qty" => (int) $request->input("replacement_qty", 0),
                "replacement_price" => (float) $request->input("replacement_price", 0),
                "money_return" => (float) $request->input("money_return", 0),
                "money_collect" => (float) $request->input("money_collect", 0),
            ]);
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
            redirect("/pos/returns/pending");
        }

        $_SESSION["flash"] = ["type" => "success", "message" => "Return settlement processed successfully."];
        redirect("/pos/returns/pending");
    }

    public function credit(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos/returns/pending");
        }

        $auth = auth_user() ?? [];
        $returnModel = new BillReturn();

        try {
            $returnModel->processCustomerCredit((int) $id, (int) ($auth["shop_id"] ?? 0), (int) ($auth["user_id"] ?? 0));
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
            redirect("/pos/returns/pending");
        }

        $_SESSION["flash"] = ["type" => "success", "message" => "Customer credit created for the return item."];
        redirect("/pos/returns/pending");
    }
}
