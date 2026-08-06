<?php

declare(strict_types=1);

namespace Bootstrap\Commands;

use Luberius\TailwindCss\TailwindCss;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ServeCommand extends Command
{
    protected static $defaultName = 'serve';
    protected static $defaultDescription = 'Serve the application and watch for Tailwind CSS changes';

    private $serverProcess;
    private $tailwindProcess;
    private $shuttingDown = false;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(['', '<info>🚀 Starting server and Tailwind watcher...</info>', '']);

        try {
            $tailwind = $this->createTailwind();
            $port = $this->findAvailablePort($output);
            if ($port === null) {
                throw new \RuntimeException('No available ports found');
            }

            $this->serverProcess = $this->startProcess([
                PHP_BINARY, '-S', "127.0.0.1:$port", '-t', 'app',
            ]);
            $this->tailwindProcess = $this->startProcess(
                $tailwind->getWatchCommand('app/css/app.css', 'app/css/app.bin.css')
            );
            $this->registerShutdown($output);
            $this->displayStarted($output, $port);

            $lastMtime = $this->getLatestFileModificationTime('app');
            while ($this->serverProcess->isRunning() && $this->tailwindProcess->isRunning()) {
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
                $this->writeProcessOutput($this->serverProcess, $output);
                $this->writeProcessOutput($this->tailwindProcess, $output);

                $currentMtime = $this->getLatestFileModificationTime('app');
                if ($currentMtime > $lastMtime) {
                    $output->writeln('<comment>🔄 PHP files changed, restarting server...</comment>');
                    $this->restartPhpServer($port, $output);
                    $lastMtime = $currentMtime;
                }
                usleep(100000);
            }

            if (!$this->shuttingDown) {
                $output->writeln('<error>A development process stopped unexpectedly.</error>');
                $this->writeProcessOutput($this->serverProcess, $output);
                $this->writeProcessOutput($this->tailwindProcess, $output);
                return Command::FAILURE;
            }
            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $output->writeln('<error>An error occurred: ' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        } finally {
            $this->stopProcesses($output);
        }
    }

    protected function findAvailablePort(OutputInterface $output, int $startPort = 6969, int $endPort = 7000): ?int
    {
        $output->writeln('<comment>🔍 Searching for an available port...</comment>');
        for ($port = $startPort; $port <= $endPort; $port++) {
            if ($this->isPortAvailable($port)) {
                $output->writeln("<info>✓ Using port $port</info>");
                return $port;
            }
        }
        return null;
    }

    protected function isPortAvailable(int $port): bool
    {
        $errno = 0;
        $error = '';
        $socket = @stream_socket_server("tcp://127.0.0.1:$port", $errno, $error);
        if ($socket === false) {
            return false;
        }
        fclose($socket);
        return true;
    }

    protected function startProcess(array $command): Process
    {
        $process = new Process($command);
        $process->setTimeout(null);
        $process->start();
        return $process;
    }

    protected function createTailwind(): TailwindCss
    {
        return new TailwindCss();
    }

    protected function getLatestFileModificationTime($dir)
    {
        if (!is_dir($dir)) {
            return 0;
        }
        $latest = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $latest = max($latest, $file->getMTime());
            }
        }
        return $latest;
    }

    protected function restartPhpServer($port, OutputInterface $output)
    {
        $this->stopProcess($this->serverProcess);
        $this->serverProcess = $this->startProcess([
            PHP_BINARY, '-S', "127.0.0.1:$port", '-t', 'app',
        ]);
        $output->writeln('<info>✅ Server restarted successfully</info>');
    }

    private function registerShutdown(OutputInterface $output): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }
        pcntl_signal(SIGINT, function () use ($output): void {
            $this->shuttingDown = true;
            $output->writeln("\n<comment>Shutdown signal received.</comment>");
            $this->stopProcesses($output);
        });
    }

    private function displayStarted(OutputInterface $output, int $port): void
    {
        $output->writeln([
            '',
            "🌐 <info>Server running on</info> <comment>http://127.0.0.1:$port</comment>",
            '🎨 <info>Tailwind CSS is watching for changes.</info>',
            '📢 <comment>Press Ctrl+C to stop.</comment>',
            '',
        ]);
    }

    private function writeProcessOutput(?Process $process, OutputInterface $output): void
    {
        if ($process === null) {
            return;
        }
        $stdout = $process->getIncrementalOutput();
        $stderr = $process->getIncrementalErrorOutput();
        if ($stdout !== '') {
            $output->write($stdout);
        }
        if ($stderr !== '') {
            $output->write('<error>' . $stderr . '</error>');
        }
    }

    private function stopProcesses(OutputInterface $output): void
    {
        if ($this->serverProcess === null && $this->tailwindProcess === null) {
            return;
        }
        $this->shuttingDown = true;
        $this->stopProcess($this->serverProcess);
        $this->stopProcess($this->tailwindProcess);
        $this->serverProcess = null;
        $this->tailwindProcess = null;
        $output->writeln('<info>✅ Development services stopped.</info>');
    }

    private function stopProcess(?Process $process): void
    {
        if ($process !== null && $process->isRunning()) {
            $process->stop(1, 15);
        }
    }
}
