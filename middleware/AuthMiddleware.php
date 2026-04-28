<?php

declare(strict_types=1);

class AuthMiddleware implements Middleware
{
    public function handle($request, $next)
    {
        if (empty($_SESSION["auth"]["user_id"])) {
            redirect("/login");
        }

        return $next();
    }
}
