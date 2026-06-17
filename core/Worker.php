<?php

namespace Core;

$queuePath = __DIR__ . "/../storage/queue/";
$files = glob($queuePath . "*.json");

foreach ($files as $file) {
    $payload = json_decode(file_get_contents($file), true);

    $jobClass = $payload["job"];

    require_once __DIR__ . "/../app/Jobs/" . $jobClass . ".php";

    $job = new $jobClass();

    $job->handle($payload["data"]);

    unlink($file);
}
