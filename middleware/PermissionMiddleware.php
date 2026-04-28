<?php

declare(strict_types=1);

class PermissionMiddleware implements Middleware
{
    public function __construct(private readonly string $permission)
    {
    }

    public function handle($request, $next)
    {
        if (empty($_SESSION["auth"]["user_id"])) {
            redirect("/login");
        }

        if (!can($this->permission)) {
            http_response_code(403);
            View::make("errors/403", [
                "title" => "Forbidden",
                "message" => "You do not have permission to access this settings area.",
            ]);
            exit();
        }

        return $next();
    }
}
