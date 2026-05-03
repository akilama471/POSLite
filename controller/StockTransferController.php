<?php

declare(strict_types=1);

class StockTransferController
{
    public function create(Request $request): void
    {
        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $model = new StockTransfer();
        $shopModel = new Shop();
        $categoryModel = new ProductCategory();

        View::make("stock/transfers/create", [
            "title" => "Stock Transfer",
            "auth" => $auth,
            "draft" => $model->draft($auth),
            "shops" => array_values(array_filter($shopModel->allOrdered(), static fn (array $shop): bool => (int) ($shop["shopid"] ?? 0) !== $shopId)),
            "categories" => $categoryModel->allOrdered(),
            "candidates" => $_SESSION["stock_transfer_candidates"] ?? [],
            "search" => $_SESSION["stock_transfer_search"] ?? ["mode" => "code", "query" => ""],
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"], $_SESSION["stock_transfer_candidates"], $_SESSION["stock_transfer_search"]);
    }

    public function outgoing(Request $request): void
    {
        $auth = auth_user() ?? [];
        $model = new StockTransfer();

        View::make("stock/transfers/outgoing", [
            "title" => "Transfer Note",
            "auth" => $auth,
            "transfers" => $model->outgoing((int) ($auth["shop_id"] ?? 0)),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function updateTarget(Request $request): void
    {
        if (!$this->validCsrf($request, "/stock/transfers/create")) {
            return;
        }

        try {
            (new StockTransfer())->setDraftTarget(auth_user() ?? [], (int) $request->input("target_shop_id", 0));
            $_SESSION["flash"] = ["type" => "success", "message" => "Transfer target updated."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/transfers/create");
    }

    public function search(Request $request): void
    {
        if (!$this->validCsrf($request, "/stock/transfers/create")) {
            return;
        }

        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $mode = trim((string) $request->input("search_mode", "code"));
        $query = trim((string) $request->input("search_query", ""));
        $model = new StockTransfer();

        $_SESSION["stock_transfer_search"] = [
            "mode" => $mode,
            "query" => $query,
        ];

        if ($query === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Enter a code or item name to search."];
            redirect("/stock/transfers/create");
        }

        $_SESSION["stock_transfer_candidates"] = $mode === "name"
            ? $model->findCandidatesByItemName($query, $shopId)
            : $model->findCandidatesByCode($query, $shopId);

        if ($_SESSION["stock_transfer_candidates"] === []) {
            $_SESSION["flash"] = ["type" => "error", "message" => "No stock records matched the current search."];
        }

        redirect("/stock/transfers/create");
    }

    public function addLine(Request $request): void
    {
        if (!$this->validCsrf($request, "/stock/transfers/create")) {
            return;
        }

        try {
            (new StockTransfer())->addDraftLine(
                auth_user() ?? [],
                (int) $request->input("object_type", 0),
                (int) $request->input("row_id", 0),
                (int) $request->input("trans_amount", 1),
            );
            $_SESSION["flash"] = ["type" => "success", "message" => "Stock line added to the transfer draft."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/transfers/create");
    }

    public function updateLine(Request $request, string $index): void
    {
        if (!$this->validCsrf($request, "/stock/transfers/create")) {
            return;
        }

        try {
            (new StockTransfer())->updateDraftLine(auth_user() ?? [], (int) $index, (int) $request->input("trans_amount", 0));
            $_SESSION["flash"] = ["type" => "success", "message" => "Transfer quantity updated."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/transfers/create");
    }

    public function removeLine(Request $request, string $index): void
    {
        if (!$this->validCsrf($request, "/stock/transfers/create")) {
            return;
        }

        try {
            (new StockTransfer())->removeDraftLine(auth_user() ?? [], (int) $index);
            $_SESSION["flash"] = ["type" => "success", "message" => "Transfer draft line removed."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/transfers/create");
    }

    public function clearDraft(Request $request): void
    {
        if (!$this->validCsrf($request, "/stock/transfers/create")) {
            return;
        }

        (new StockTransfer())->clearDraft(auth_user() ?? []);
        $_SESSION["flash"] = ["type" => "success", "message" => "Current transfer draft cleared."];
        redirect("/stock/transfers/create");
    }

    public function submitDraft(Request $request): void
    {
        if (!$this->validCsrf($request, "/stock/transfers/create")) {
            return;
        }

        try {
            $transferId = (new StockTransfer())->submitDraft(auth_user() ?? []);
            $_SESSION["flash"] = ["type" => "success", "message" => "Stock transfer " . $transferId . " created successfully."];
            redirect("/stock/transfers");
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
            redirect("/stock/transfers/create");
        }
    }

    public function incoming(Request $request): void
    {
        $auth = auth_user() ?? [];
        $model = new StockTransfer();

        View::make("stock/transfers/incoming", [
            "title" => "Transfer Received",
            "auth" => $auth,
            "transfers" => $model->incomingPending((int) ($auth["shop_id"] ?? 0)),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function markInTransit(Request $request, string $id): void
    {
        if (!$this->validCsrf($request, "/stock/transfers")) {
            return;
        }

        $model = new StockTransfer();

        try {
            $model->markInTransit($id, (int) ((auth_user() ?? [])["shop_id"] ?? 0));
            $_SESSION["flash"] = ["type" => "success", "message" => "Transfer note marked as in transit."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/transfers");
    }

    public function accept(Request $request, string $id): void
    {
        if (!$this->validCsrf($request, "/stock/transfers/received")) {
            return;
        }

        $model = new StockTransfer();

        try {
            $model->acceptReceived($id, (int) ((auth_user() ?? [])["shop_id"] ?? 0));
            $_SESSION["flash"] = ["type" => "success", "message" => "Received transfer accepted successfully."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/transfers/received");
    }

    public function complain(Request $request, string $id): void
    {
        if (!$this->validCsrf($request, "/stock/transfers/received")) {
            return;
        }

        $auth = auth_user() ?? [];
        $model = new StockTransfer();

        try {
            $model->raiseComplaint(
                $id,
                (int) ($auth["shop_id"] ?? 0),
                (int) ($auth["user_id"] ?? 0),
                trim((string) $request->input("complaint_reason", "")),
            );
            $_SESSION["flash"] = ["type" => "success", "message" => "Transfer complaint raised successfully."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/transfers/received");
    }

    public function complaints(Request $request): void
    {
        $auth = auth_user() ?? [];
        if ((int) ($auth["user_id"] ?? -1) !== 0) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Only the administrator can manage stock transfer complaints."];
            redirect("/dashboard");
        }

        $model = new StockTransfer();

        View::make("stock/transfers/complaints", [
            "title" => "Stock Error Handling",
            "auth" => $auth,
            "transfers" => $model->complaintQueue((int) ($auth["shop_id"] ?? 0)),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function resolveComplaint(Request $request, string $id): void
    {
        if (!$this->validCsrf($request, "/stock/transfers/complaints")) {
            return;
        }

        $auth = auth_user() ?? [];
        if ((int) ($auth["user_id"] ?? -1) !== 0) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Only the administrator can manage stock transfer complaints."];
            redirect("/dashboard");
        }

        $model = new StockTransfer();
        $action = (string) $request->input("recovery_action", "");

        try {
            $model->resolveComplaint(
                $id,
                trim((string) $request->input("recovery_note", "")),
                $action,
                (int) ($auth["shop_id"] ?? 0),
            );
            $_SESSION["flash"] = ["type" => "success", "message" => "Transfer complaint updated successfully."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/transfers/complaints");
    }

    public function printNote(Request $request, string $id): void
    {
        $auth = auth_user() ?? [];
        $model = new StockTransfer();
        $transfer = $model->findWithLogs($id);

        if ($transfer === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Transfer note was not found."];
            redirect("/stock/transfers");
        }

        // Fetch company info for header
        $companyModel = new Company();
        $company = $companyModel->primary();

        // Find transfer handle by user name
        $handlerName = "User Not Found";
        if (($transfer["transfer_user_id"] ?? 0) > 0) {
            $userModel = new User();
            $handler = $userModel->findById((int) $transfer["transfer_user_id"]);
            if ($handler !== null) {
                $handlerName = $handler["visibledata"] ?? "";
            }
        }

        View::make("stock/transfers/print", [
            "title" => "Download My Transfer Note",
            "auth" => $auth,
            "transfer" => $transfer,
            "company" => $company,
            "handlerName" => $handlerName,
        ], "print");
    }

    private function validCsrf(Request $request, string $redirectPath): bool
    {
        if (verify_csrf((string) $request->input("_token"))) {
            return true;
        }

        $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
        redirect($redirectPath);
    }
}
