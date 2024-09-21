<?php

namespace Bootstrap\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Luberius\TailwindCss\TailwindCss;
use Symfony\Component\Process\Process;

class ServeCommand extends Command
{
    protected static $defaultName = 'serve';

    protected function configure()
    {
        $this->setDescription('Serve the application and watch for Tailwind CSS changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting the server and Tailwind CSS watcher...');

        $tailwind = new TailwindCss();
        $serverProcess = new Process(['php', '-S', 'localhost:6969', '-t', 'app']);
        $tailwindProcess = new Process(explode(' ', $tailwind->getWatchCommand('app/css/app.css', 'app/css/app.bin.css')));

        $serverProcess->start();
        $tailwindProcess->start();

        $output->writeln('Server running on http://localhost:6969');
        $output->writeln('Tailwind CSS watching for changes...');

        while ($serverProcess->isRunning() && $tailwindProcess->isRunning()) {
            sleep(1);
        }

        return Command::SUCCESS;
    }
}
