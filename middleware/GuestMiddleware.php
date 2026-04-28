<?php

declare(strict_types=1);

class GuestMiddleware implements Middleware
{
    public function handle($request, $next)
    {
        if (!empty($_SESSION["auth"]["user_id"])) {
            redirect("/dashboard");
        }

        return $next();
    }
}
