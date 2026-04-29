<?php

declare(strict_types=1);

class RechargeOperatorController
{
    public function index(Request $request): void
    {
        $operatorModel = new RechargeOperator();

        View::make("catalog/operators/index", [
            "title" => "Manage Operator",
            "auth" => auth_user(),
            "operators" => $operatorModel->allOrdered(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/operators");
        }

        $name = trim((string) $request->input("operator_name", ""));
        $operatorModel = new RechargeOperator();

        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Operator name is required."];
            redirect("/operators");
        }

        if ($operatorModel->existsByName($name)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That operator already exists."];
            redirect("/operators");
        }

        $operatorModel->createOperator($name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Operator created successfully."];
        redirect("/operators");
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/operators");
        }

        $operatorId = (int) $id;
        $name = trim((string) $request->input("operator_name", ""));
        $operatorModel = new RechargeOperator();

        if ($operatorModel->findById($operatorId) === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Operator not found."];
            redirect("/operators");
        }

        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Operator name is required."];
            redirect("/operators");
        }

        if ($operatorModel->existsByName($name, $operatorId)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That operator name is already in use."];
            redirect("/operators");
        }

        $operatorModel->updateOperator($operatorId, $name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Operator updated successfully."];
        redirect("/operators");
    }
}
