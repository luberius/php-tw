<?php

require __DIR__ . '/../bootstrap/env.php';
require __DIR__ . '/../bootstrap/error-handler.php';

loadEnv(__DIR__ . '/../.env');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) env('APP_NAME', 'PHP-TW'), ENT_QUOTES, 'UTF-8') ?></title>
    <link href="/css/app.bin.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-8">
        <h1 class="text-3xl font-bold text-center">
            Welcome to <?= htmlspecialchars((string) env('APP_NAME', 'PHP-TW'), ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <p class="text-center mt-4 text-gray-600">
            Environment: <?= htmlspecialchars((string) env('APP_ENV', 'development'), ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
</body>
</html>
