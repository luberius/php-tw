<?php

namespace Tests\Unit\Bootstrap\Commands;

use PHPUnit\Framework\TestCase;
use Bootstrap\Commands\ServeCommand;

require_once __DIR__ . '/../../../../stubs/default/bootstrap/commands/ServeCommand.php';

class ServeCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/serve_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public function test_it_detects_latest_modification_time_in_directory(): void
    {
        $file1 = $this->tempDir . '/file1.php';
        $file2 = $this->tempDir . '/file2.php';

        file_put_contents($file1, '<?php');
        sleep(1);
        file_put_contents($file2, '<?php');

        $command = new ServeCommandTestDouble();
        $latest = $command->exposedGetLatestFileModificationTime($this->tempDir);

        $this->assertSame(filemtime($file2), $latest);
        $this->assertGreaterThan(filemtime($file1), $latest);
    }

    public function test_it_returns_zero_for_empty_directory(): void
    {
        $command = new ServeCommandTestDouble();
        $result = $command->exposedGetLatestFileModificationTime($this->tempDir);

        $this->assertSame(0, $result);
    }

    public function test_it_only_checks_php_files(): void
    {
        file_put_contents($this->tempDir . '/file.php', '<?php');
        file_put_contents($this->tempDir . '/file.txt', 'text');
        file_put_contents($this->tempDir . '/file.js', 'js');

        $phpTime = filemtime($this->tempDir . '/file.php');

        sleep(1);
        touch($this->tempDir . '/file.txt');
        touch($this->tempDir . '/file.js');

        $command = new ServeCommandTestDouble();
        $latest = $command->exposedGetLatestFileModificationTime($this->tempDir);

        $this->assertSame($phpTime, $latest);
    }

    public function test_it_ignores_non_php_files(): void
    {
        file_put_contents($this->tempDir . '/README.md', 'readme');
        file_put_contents($this->tempDir . '/style.css', 'css');
        file_put_contents($this->tempDir . '/script.js', 'js');

        $command = new ServeCommandTestDouble();
        $result = $command->exposedGetLatestFileModificationTime($this->tempDir);

        $this->assertSame(0, $result);
    }

    public function test_it_handles_nested_directories(): void
    {
        $nestedDir = $this->tempDir . '/nested/deep';
        mkdir($nestedDir, 0777, true);

        $file1 = $this->tempDir . '/root.php';
        $file2 = $nestedDir . '/nested.php';

        file_put_contents($file1, '<?php');
        sleep(1);
        file_put_contents($file2, '<?php');

        $command = new ServeCommandTestDouble();
        $latest = $command->exposedGetLatestFileModificationTime($this->tempDir);

        $this->assertSame(filemtime($file2), $latest);
    }

    public function test_it_returns_most_recent_time_across_multiple_files(): void
    {
        $times = [];

        for ($i = 0; $i < 5; $i++) {
            $file = $this->tempDir . "/file{$i}.php";
            file_put_contents($file, '<?php');
            $times[] = filemtime($file);
            usleep(100000);
        }

        $command = new ServeCommandTestDouble();
        $latest = $command->exposedGetLatestFileModificationTime($this->tempDir);

        $this->assertSame(max($times), $latest);
    }

    public function test_it_handles_directory_with_dots(): void
    {
        file_put_contents($this->tempDir . '/file.php', '<?php');

        $command = new ServeCommandTestDouble();
        $result = $command->exposedGetLatestFileModificationTime($this->tempDir);

        $this->assertGreaterThan(0, $result);
    }

    private function removeDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        if (!is_dir($dir)) {
            unlink($dir);
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}

class ServeCommandTestDouble extends ServeCommand
{
    public function exposedGetLatestFileModificationTime($dir)
    {
        return $this->getLatestFileModificationTime($dir);
    }
}
