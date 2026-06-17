<?php

namespace Core;

class Queue
{
    public static function push($jobClass, $data = [])
    {
        $payload = [
            "job" => $jobClass,
            "data" => $data,
            "created_at" => time(),
        ];

        $filename = uniqid() . ".json";

        file_put_contents(
            __DIR__ . "/../storage/queue/" . $filename,
            json_encode($payload),
        );
    }
}
