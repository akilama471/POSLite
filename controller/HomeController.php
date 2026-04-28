<?php

class HomeController
{
    public function index()
    {
        View::make("dashboard", [
            "username" => "Akila",
            "role" => "admin",
        ]);
    }

    public function dashboard()
    {
        echo "Dashboard Page";
    }
}
