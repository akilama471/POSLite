<?php

class View
{
    public static function make($view, $data = [], $layout = "layout")
    {
        $viewPath = "../views/{$view}.php";
        $layoutPath = "../views/layout/{$layout}.php";

        if (!file_exists($viewPath)) {
            die("View not found: {$view}");
        }

        extract($data);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require $layoutPath;
    }
}
