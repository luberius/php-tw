<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Luberius\TailwindCss\TailwindCss;

class BuildCommand extends Command
{
    protected static $defaultName = 'build';
    protected static $defaultDescription = 'Build production-optimized assets';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>🚀 Building for production...</info>');
        $output->writeln('');

        $this->buildCSS($output);
        $this->optimizeAutoloader($output);
        $this->generateOpcachePreload($output);
        $this->createProductionHtaccess($output);

        $output->writeln('');
        $output->writeln('<info>✅ Production build complete!</info>');
        $output->writeln('');

        $this->displayChecklist($output);

        return Command::SUCCESS;
    }

    private function buildCSS(OutputInterface $output): void
    {
        $output->write('📦 Building minified CSS... ');

        try {
            $tailwind = new TailwindCss();
            $binPath = $tailwind->getBinPath();

            $process = new Process([
                $binPath,
                '-i', 'app/css/app.css',
                '-o', 'app/css/app.bin.css',
                '--minify'
            ]);

            $process->run();

            if ($process->isSuccessful()) {
                $output->writeln('<info>✓</info>');
            } else {
                $output->writeln('<error>✗</error>');
                $output->writeln('<error>' . $process->getErrorOutput() . '</error>');
            }
        } catch (\Exception $e) {
            $output->writeln('<error>✗ ' . $e->getMessage() . '</error>');
        }
    }

    private function optimizeAutoloader(OutputInterface $output): void
    {
        $output->write('⚡ Optimizing Composer autoloader... ');

        $process = new Process([
            'composer',
            'dump-autoload',
            '--optimize',
            '--classmap-authoritative',
            '--no-dev'
        ]);

        $process->run();

        if ($process->isSuccessful()) {
            $output->writeln('<info>✓</info>');
        } else {
            $output->writeln('<error>✗</error>');
        }
    }

    private function generateOpcachePreload(OutputInterface $output): void
    {
        $output->write('🔥 Generating OPcache preload file... ');

        $vendorDir = __DIR__ . '/../../vendor';
        $preloadFile = __DIR__ . '/../opcache-preload.php';

        if (!file_exists($vendorDir . '/composer/autoload_classmap.php')) {
            $output->writeln('<comment>⚠ Skipped (run after composer install)</comment>');
            return;
        }

        $classmap = require $vendorDir . '/composer/autoload_classmap.php';

        $preload = "<?php\n\n";
        $preload .= "// Auto-generated OPcache preload file\n";
        $preload .= "// Load this file in php.ini: opcache.preload=" . $preloadFile . "\n\n";

        foreach ($classmap as $file) {
            if (file_exists($file)) {
                $preload .= "opcache_compile_file('" . addslashes($file) . "');\n";
            }
        }

        file_put_contents($preloadFile, $preload);
        $output->writeln('<info>✓</info>');
    }

    private function createProductionHtaccess(OutputInterface $output): void
    {
        $output->write('🔒 Creating production .htaccess... ');

        $htaccess = <<<'HTACCESS'
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
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-javascript "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
</IfModule>

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

HTACCESS;

        file_put_contents(__DIR__ . '/../../.htaccess.production', $htaccess);
        $output->writeln('<info>✓</info>');
    }

    private function displayChecklist(OutputInterface $output): void
    {
        $output->writeln('<comment>📋 Deployment Checklist:</comment>');
        $output->writeln('');
        $output->writeln('   □ Set <info>APP_ENV=production</info> in .env');
        $output->writeln('   □ Set <info>APP_DEBUG=false</info> in .env');
        $output->writeln('   □ Enable OPcache in php.ini:');
        $output->writeln('      opcache.enable=1');
        $output->writeln('      opcache.memory_consumption=128');
        $output->writeln('      opcache.validate_timestamps=0');
        $output->writeln('   □ Set OPcache preload in php.ini:');
        $output->writeln('      opcache.preload=' . realpath(__DIR__ . '/../opcache-preload.php'));
        $output->writeln('   □ Rename .htaccess.production to .htaccess (if using Apache)');
        $output->writeln('   □ Ensure file permissions are correct (644 for files, 755 for directories)');
        $output->writeln('');
    }
}

return new BuildCommand();
