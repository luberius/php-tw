<?php

function removeDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!removeDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

// Copy .gitignore if it doesn't exist
if (!file_exists('.gitignore') && file_exists('stubs/default/.gitignore')) {
    copy('stubs/default/.gitignore', '.gitignore');
}

// Remove stubs directory
removeDirectory('stubs');

// Remove README.md
if (file_exists('README.md')) {
    unlink('README.md');
}

// Display welcome message
if (file_exists('welcome.php')) {
    include 'welcome.php';
    unlink('welcome.php');
}

echo "Post-creation tasks completed successfully.\n";

// Remove this script
unlink(__FILE__);
