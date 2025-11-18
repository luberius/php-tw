<?php

if (!function_exists('loadEnv')) {
    function loadEnv(string $path): void
    {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        $value = trim($value, '"\'');

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false) {
        return $default;
    }

    if ($value === 'true' || $value === '(true)') {
        return true;
    }

    if ($value === 'false' || $value === '(false)') {
        return false;
    }

    if ($value === 'null' || $value === '(null)') {
        return null;
    }

    return $value;
    }
}
