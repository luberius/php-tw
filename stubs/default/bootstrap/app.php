<?php

use Symfony\Component\Console\Application;

$app = new class extends Application {
    public function handleCommand($input)
    {
        return $this->run($input);
    }
};

$commandFiles = glob(__DIR__ . '/commands/*.php');
foreach ($commandFiles as $commandFile) {
    require_once $commandFile;
    $commandClass = 'Bootstrap\\Commands\\' . basename($commandFile, '.php');
    $app->add(new $commandClass());
}

return $app;
