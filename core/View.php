<?php

declare(strict_types=1);

class View
{
    public static function make(string $view, array $data = [], string $layout = "layout"): void
    {
        $viewPath = BASE_PATH . "/views/{$view}.php";
        $layoutPath = BASE_PATH . "/views/layout/{$layout}.php";

        if (!is_file($viewPath)) {
            throw new RuntimeException("View not found: {$view}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require $layoutPath;
    }
}
