<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;

$app = new Application('wand', '1.1.0');

$commandFiles = glob(__DIR__ . '/commands/*.php');
foreach ($commandFiles as $commandFile) {
    require_once $commandFile;
    $commandClass = 'Bootstrap\\Commands\\' . basename($commandFile, '.php');
    if (!class_exists($commandClass)) {
        throw new RuntimeException("Command file did not define $commandClass: $commandFile");
    }
    $app->add(new $commandClass());
}

return $app;
