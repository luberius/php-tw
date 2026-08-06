<?php

declare(strict_types=1);

namespace Bootstrap\Commands;

use Luberius\TailwindCss\TailwindCss;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class BuildCommand extends Command
{
    protected static $defaultName = 'build';
    protected static $defaultDescription = 'Build production-optimized assets';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>🚀 Building for production...</info>');
        $output->writeln('');

        $successful = $this->buildCss($output);
        $successful = $this->optimizeAutoloader($output) && $successful;
        $successful = $this->generateOpcachePreload($output) && $successful;
        $successful = $this->createProductionHtaccess($output) && $successful;

        $output->writeln('');
        if (!$successful) {
            $output->writeln('<error>Production build failed.</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>✅ Production build complete!</info>');
        $output->writeln('');
        $this->displayChecklist($output);
        return Command::SUCCESS;
    }

    protected function buildCss(OutputInterface $output): bool
    {
        $output->write('📦 Building minified CSS... ');
        try {
            $tailwind = $this->createTailwind();
            $process = $this->createProcess([
                $tailwind->getBinPath(),
                '-i', 'app/css/app.css',
                '-o', 'app/css/app.bin.css',
                '--minify',
            ]);
            $process->run();
        } catch (\Throwable $exception) {
            $output->writeln('<error>✗ ' . $exception->getMessage() . '</error>');
            return false;
        }

        return $this->reportProcessResult($process, $output);
    }

    protected function optimizeAutoloader(OutputInterface $output): bool
    {
        $output->write('⚡ Optimizing Composer autoloader... ');
        $process = $this->createProcess([
            'composer', 'dump-autoload', '--optimize', '--classmap-authoritative', '--no-dev',
        ]);
        $process->run();
        return $this->reportProcessResult($process, $output);
    }

    protected function generateOpcachePreload(OutputInterface $output): bool
    {
        $output->write('🔥 Generating OPcache preload file... ');
        $vendorDir = __DIR__ . '/../../vendor';
        $preloadFile = __DIR__ . '/../opcache-preload.php';
        $classmapFile = $vendorDir . '/composer/autoload_classmap.php';

        if (!file_exists($classmapFile)) {
            $output->writeln('<comment>⚠ Skipped (run after composer install)</comment>');
            return true;
        }

        $preload = "<?php\n\n// Auto-generated OPcache preload file\n";
        $preload .= '// Load this file in php.ini: opcache.preload=' . $preloadFile . "\n\n";
        foreach (require $classmapFile as $file) {
            if (is_string($file) && file_exists($file)) {
                $preload .= 'opcache_compile_file(' . var_export($file, true) . ");\n";
            }
        }

        if ($this->writeFile($preloadFile, $preload) === false) {
            $output->writeln('<error>✗ Failed to write OPcache preload file</error>');
            return false;
        }
        $output->writeln('<info>✓</info>');
        return true;
    }

    protected function createProductionHtaccess(OutputInterface $output): bool
    {
        $output->write('🔒 Creating production .htaccess... ');
        $contents = <<<'HTACCESS'
# PHP-TW Production .htaccess

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
</IfModule>

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

HTACCESS;

        if ($this->writeFile(__DIR__ . '/../../.htaccess.production', $contents) === false) {
            $output->writeln('<error>✗ Failed to write production .htaccess</error>');
            return false;
        }
        $output->writeln('<info>✓</info>');
        return true;
    }

    protected function createTailwind(): TailwindCss
    {
        return new TailwindCss();
    }

    protected function createProcess(array $command): Process
    {
        return new Process($command);
    }

    protected function writeFile(string $path, string $contents)
    {
        return file_put_contents($path, $contents);
    }

    private function reportProcessResult(Process $process, OutputInterface $output): bool
    {
        if ($process->isSuccessful()) {
            $output->writeln('<info>✓</info>');
            return true;
        }

        $output->writeln('<error>✗</error>');
        $error = trim($process->getErrorOutput() ?: $process->getOutput());
        if ($error !== '') {
            $output->writeln('<error>' . $error . '</error>');
        }
        return false;
    }

    private function displayChecklist(OutputInterface $output): void
    {
        $output->writeln('<comment>📋 Deployment Checklist:</comment>');
        $output->writeln('   □ Set APP_ENV=production and APP_DEBUG=false in .env');
        $output->writeln('   □ Enable and configure OPcache');
        $output->writeln('   □ Set opcache.preload=' . realpath(__DIR__ . '/../opcache-preload.php'));
        $output->writeln('   □ Rename .htaccess.production to .htaccess when using Apache');
    }
}
