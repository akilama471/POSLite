<?php

declare(strict_types=1);

class BillReturnController
{
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
}
