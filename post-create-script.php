<?php

declare(strict_types=1);

function copyTemplateDirectory(string $source, string $destination): void
{
    if (is_link($source) || !is_dir($source)) {
        throw new RuntimeException("Unsafe or missing template directory: $source");
    }
    if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
        throw new RuntimeException("Failed to create directory: $destination");
    }

    $iterator = new DirectoryIterator($source);
    foreach ($iterator as $entry) {
        if ($entry->isDot()) {
            continue;
        }
        $sourcePath = $entry->getPathname();
        $destinationPath = $destination . DIRECTORY_SEPARATOR . $entry->getFilename();
        if ($entry->isLink()) {
            throw new RuntimeException("Refusing to copy template symlink: $sourcePath");
        }
        if ($entry->isDir()) {
            copyTemplateDirectory($sourcePath, $destinationPath);
        } elseif (!$entry->isFile() || !copy($sourcePath, $destinationPath)) {
            throw new RuntimeException("Failed to copy template file: $sourcePath");
        }
    }
}

function removeTemplateDirectory(string $directory): void
{
    if (!file_exists($directory) && !is_link($directory)) {
        return;
    }
    if (is_link($directory)) {
        throw new RuntimeException("Refusing to traverse template symlink: $directory");
    }
    if (!is_dir($directory)) {
        if (!unlink($directory)) {
            throw new RuntimeException("Failed to remove file: $directory");
        }
        return;
    }
    foreach (new DirectoryIterator($directory) as $entry) {
        if (!$entry->isDot()) {
            removeTemplateDirectory($entry->getPathname());
        }
    }
    if (!rmdir($directory)) {
        throw new RuntimeException("Failed to remove directory: $directory");
    }
}

function runPostCreate(string $projectRoot): void
{
    $stubs = $projectRoot . '/stubs/default';
    if (!is_dir($stubs)) {
        throw new RuntimeException('The stubs/default template directory is missing.');
    }

    copyTemplateDirectory($stubs, $projectRoot);
    echo "Copied the default project template.\n";

    $exampleEnv = $projectRoot . '/.env.example';
    $environment = $projectRoot . '/.env';
    if (is_file($exampleEnv) && !file_exists($environment) && !copy($exampleEnv, $environment)) {
        throw new RuntimeException('Failed to create .env from .env.example.');
    }

    $welcome = $projectRoot . '/welcome.php';
    if (is_file($welcome)) {
        require $welcome;
        if (!unlink($welcome)) {
            throw new RuntimeException('Failed to remove welcome.php.');
        }
    }

    removeTemplateDirectory($projectRoot . '/stubs');
    $script = $projectRoot . '/post-create-script.php';
    if (is_file($script) && !unlink($script)) {
        throw new RuntimeException('Failed to remove post-create-script.php.');
    }
    echo "Project creation completed successfully.\n";
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    runPostCreate(__DIR__);
}
