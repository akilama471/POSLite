<?php

class AdminMiddleware
{
    public function handle($request, $next)
    {
        if ($_SESSION["role"] !== "admin") {
            http_response_code(403);
            echo "403 Forbidden";
            exit();
        }

        return $next();
    }
}
