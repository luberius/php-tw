<?php

namespace Luberius\PhpTw\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class NewCommand extends Command
{
    protected static $defaultName = 'new';

    protected function configure()
    {
        $this
            ->setDescription('Create a new PHP-TW project')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the project');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $output->writeln("Creating new PHP-TW project: $name");

        // Create project directory
        mkdir($name);
        chdir($name);

        // Copy stub files
        $this->copyStubs();

        // Install dependencies
        $composerInstall = new Process(['composer', 'install']);
        $composerInstall->run();

        $output->writeln("Project created successfully!");
        $output->writeln("cd $name && php wand serve to start your project.");

        return Command::SUCCESS;
    }

    private function copyStubs()
    {
        $stubsDir = PHPTW_ROOT . '/stubs/default';
        $this->recursiveCopy($stubsDir, getcwd());
    }

    private function recursiveCopy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (($file = readdir($dir)) !== false) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);

        // Copy hidden files (like .gitignore)
        foreach (glob($src . '/.*') as $hidden_file) {
            $file_name = basename($hidden_file);
            if ($file_name != '.' && $file_name != '..') {
                copy($hidden_file, $dst . '/' . $file_name);
            }
        }
    }
}
