<?php

namespace Bootstrap\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Luberius\TailwindCss\TailwindCss;

class ServeCommand extends Command
{
    protected static $defaultName = 'serve';
    private $serverProcess;
    private $tailwindProcess;

    protected function configure()
    {
        $this->setDescription('Serve the application and watch for Tailwind CSS changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            "",
            "<info>🚀 Starting server and Tailwind watcher...</info>",
            ""
        ]);

        try {
            $tailwind = new TailwindCss();
            
            $tailwindCommand = $tailwind->getBinPath();
            $inputCss = 'app/css/app.css';
            $outputCss = 'app/css/app.bin.css';
            
            $port = $this->findAvailablePort($output);
            if ($port === false) {
                throw new \RuntimeException("No available ports found");
            }
            
            $this->serverProcess = $this->startProcess("php -S 127.0.0.1:$port -t app");
            $this->tailwindProcess = $this->startProcess("$tailwindCommand -i $inputCss -o $outputCss --watch");

            $output->writeln([
                "",
                "🌐 <info>Server running on</info> <comment>http://127.0.0.1:$port</comment>",
                "🎨 <info>Tailwind CSS watching for changes...</info>",
                "",
                "📢 <comment>Press Ctrl+C to stop the server and watcher.</comment>",
                ""
            ]);

            $this->registerShutdown($output);

            while ($this->isProcessRunning($this->serverProcess) && $this->isProcessRunning($this->tailwindProcess)) {
                usleep(100000); // Sleep for 100ms to reduce CPU usage
            }

            if (!$this->isProcessRunning($this->serverProcess)) {
                $output->writeln('<error>PHP server process stopped unexpectedly</error>');
            }
            if (!$this->isProcessRunning($this->tailwindProcess)) {
                $output->writeln('<error>Tailwind process stopped unexpectedly</error>');
            }

        } catch (\Exception $e) {
            $output->writeln('<error>An error occurred: ' . $e->getMessage() . '</error>');
            $this->stopProcesses($output);
            return Command::FAILURE;
        }

        $this->stopProcesses($output);
        return Command::SUCCESS;
    }

    private function findAvailablePort(OutputInterface $output, $startPort = 6969, $endPort = 7000)
    {
        $output->writeln("<comment>🔍 Searching for an available port...</comment>");
        for ($port = $startPort; $port <= $endPort; $port++) {
            $output->write("  Testing port $port... ");
            if ($this->isPortAvailable($port)) {
                $output->writeln("<info>✅ Available!</info>");
                return $port;
            } else {
                $output->writeln("<error>❌ In use</error>");
            }
        }
        
        $output->writeln("<error>😕 No available ports found between $startPort and $endPort.</error>");
        return false;
    }

    private function isPortAvailable($port)
    {
        $addresses = ['127.0.0.1', '::1', 'localhost'];
        
        foreach ($addresses as $address) {
            // Check using socket creation
            $sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($sock === false) {
                continue;
            }

            // Set socket options to allow reuse of the address
            socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

            $result = @socket_bind($sock, $address, $port);
            
            if ($result !== false) {
                socket_close($sock);
                return true;  // Port is available on this address
            }

            socket_close($sock);

            // Double-check using fsockopen
            $conn = @fsockopen($address, $port, $errno, $errstr, 0.1);
            if ($conn !== false) {
                fclose($conn);
                return false;  // Port is in use
            }
        }

        return false;  // Port is not available on any tested address
    }

    private function startProcess($command)
    {
        $descriptorspec = array(
           0 => array("pipe", "r"),
           1 => array("pipe", "w"),
           2 => array("pipe", "w")
        );

        // Replace 'localhost' with '127.0.0.1' in the command
        $command = str_replace('localhost', '127.0.0.1', $command);

        $process = proc_open($command, $descriptorspec, $pipes, null, null, ['bypass_shell' => true]);

        if (is_resource($process)) {
            stream_set_blocking($pipes[1], 0);
            stream_set_blocking($pipes[2], 0);
            return ['process' => $process, 'pipes' => $pipes];
        }

        throw new \RuntimeException("Failed to start process: $command");
    }

    private function isProcessRunning($processInfo)
    {
        $status = proc_get_status($processInfo['process']);
        return $status['running'];
    }

    private function registerShutdown(OutputInterface $output)
    {
        pcntl_signal(SIGINT, function () use ($output) {
            $output->writeln("\n<comment>Shutdown signal received.</comment>");
            $this->stopProcesses($output);
            exit;
        });
    }

    private function stopProcesses(OutputInterface $output)
    {
        $output->writeln([
            "",
            "<comment>🛑 Shutting down services...</comment>"
        ]);

        if ($this->serverProcess) {
            $this->terminateProcess($this->serverProcess, 'PHP server', $output);
        }

        if ($this->tailwindProcess) {
            $this->terminateProcess($this->tailwindProcess, 'Tailwind CSS watcher', $output);
        }

        $output->writeln("<info>✅ Shutdown complete.</info>");
    }

    private function terminateProcess($processInfo, $name, OutputInterface $output)
    {
        proc_terminate($processInfo['process'], SIGTERM);
        $output->writeln("  <info>✅ $name stopped.</info>");
        foreach ($processInfo['pipes'] as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($processInfo['process']);
    }
}
