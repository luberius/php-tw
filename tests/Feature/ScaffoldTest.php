<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Process\Process;

class ScaffoldTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        $this->projectRoot = sys_get_temp_dir() . '/php-tw-scaffold-' . bin2hex(random_bytes(6));
        mkdir($this->projectRoot, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectRoot);
    }

    public function testPostCreateProducesRunnableProjectStructure(): void
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $this->copyDirectory($repositoryRoot . '/stubs', $this->projectRoot . '/stubs');
        copy($repositoryRoot . '/post-create-script.php', $this->projectRoot . '/post-create-script.php');

        $process = new Process([PHP_BINARY, $this->projectRoot . '/post-create-script.php']);
        $process->mustRun();

        self::assertFileExists($this->projectRoot . '/wand');
        self::assertFileExists($this->projectRoot . '/bootstrap/commands/BuildCommand.php');
        self::assertFileExists($this->projectRoot . '/bootstrap/commands/ServeCommand.php');
        self::assertFileExists($this->projectRoot . '/.env');
        self::assertDirectoryDoesNotExist($this->projectRoot . '/stubs');
        self::assertFileDoesNotExist($this->projectRoot . '/post-create-script.php');
        self::assertFileDoesNotExist($this->projectRoot . '/welcome.php');
    }

    public function testBootstrapDiscoversBuildAndServeCommands(): void
    {
        /** @var Application $application */
        $application = require dirname(__DIR__, 2) . '/stubs/default/bootstrap/app.php';

        self::assertTrue($application->has('build'));
        self::assertTrue($application->has('serve'));
    }

    public function testTemplateContainsNoSymlinks(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                dirname(__DIR__, 2) . '/stubs/default',
                \FilesystemIterator::SKIP_DOTS
            )
        );
        foreach ($iterator as $file) {
            self::assertFalse($file->isLink(), $file->getPathname());
        }
    }

    private function copyDirectory(string $source, string $destination): void
    {
        mkdir($destination, 0755, true);
        foreach (new \DirectoryIterator($source) as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            $target = $destination . '/' . $entry->getFilename();
            $entry->isDir()
                ? $this->copyDirectory($entry->getPathname(), $target)
                : copy($entry->getPathname(), $target);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        foreach (new \DirectoryIterator($directory) as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            $entry->isDir()
                ? $this->removeDirectory($entry->getPathname())
                : unlink($entry->getPathname());
        }
        rmdir($directory);
    }
}
