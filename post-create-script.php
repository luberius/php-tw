<?php

function copyDirectory($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyDirectory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

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

// Copy contents from stubs/default to project root
if (is_dir('stubs/default')) {
    copyDirectory('stubs/default', '.');
    echo "Copied stubs/default contents to project root.\n";
}

// Remove stubs directory
removeDirectory('stubs');
echo "Removed stubs directory.\n";

// Display welcome message
if (file_exists('welcome.php')) {
    include 'welcome.php';
    unlink('welcome.php');
    echo "Displayed welcome message and removed welcome.php.\n";
}

echo "Post-creation tasks completed successfully.\n";

// Remove this script
unlink(__FILE__);
echo "Removed post-create-script.php.\n";
