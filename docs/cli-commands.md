# CLI Commands

PHP-TW uses the `wand` CLI tool for project management tasks. All commands work seamlessly with Tailwind CSS v4.

## Usage

```bash
php wand <command> [options]
```

## Available Commands

### `serve`

Starts a development server with live Tailwind CSS compilation.

**Usage**:
```bash
php wand serve
```

**What it does**:
1. Finds an available port (searches 6969-7000)
2. Starts PHP built-in development server on `127.0.0.1:PORT`
3. Starts Tailwind CSS watcher for live compilation
4. Monitors both processes continuously
5. Handles graceful shutdown on `Ctrl+C`

**Output**:
```
Server started on http://127.0.0.1:6969
Press Ctrl+C to stop

  INFO  [PHP] PHP 8.3.0 Development Server (http://127.0.0.1:6969) started
  INFO  [Tailwind] Rebuilding...
  INFO  [Tailwind] Done in 142ms
```

**Port Discovery**:
- Default range: 6969-7000
- Uses socket binding test to find free port
- Falls back to `fsockopen` if socket creation fails
- Exits with error if no port available

**Process Management**:
- **PHP Server**: `php -S 127.0.0.1:PORT -t app`
  - Serves files from `app/` directory
  - `app/index.php` is the entry point

- **Tailwind Watcher**: `tailwindcss -i app/css/app.css -o app/css/app.bin.css --watch`
  - Auto-detects content files (no config needed)
  - Compiles `app/css/app.css` → `app/css/app.bin.css`
  - Uses Tailwind CSS v4 with CSS-based configuration
  - Runs continuously until stopped

**Signal Handling**:
- Registers POSIX signal handler for `SIGINT` (Ctrl+C)
- Gracefully terminates both processes
- Ensures clean shutdown

**Error Handling**:
- Monitors process health every 100ms
- Alerts if PHP server stops unexpectedly
- Alerts if Tailwind watcher stops unexpectedly
- Exits with appropriate error messages

**Troubleshooting**:

| Issue | Solution |
|-------|----------|
| Port already in use | Server auto-finds next available port |
| Tailwind binary not found | Will auto-download v4 on first run (requires internet) |
| PHP server crashes | Check PHP error logs, verify `app/` exists |
| CSS not compiling | Verify `app/css/app.css` has `@import "tailwindcss"` |

### `list`

Lists all available commands (Symfony Console built-in).

**Usage**:
```bash
php wand list
```

### `help`

Shows help for a specific command (Symfony Console built-in).

**Usage**:
```bash
php wand help serve
```

## Adding Custom Commands

Commands are auto-discovered from `bootstrap/commands/*.php`.

**Example**: Create `bootstrap/commands/BuildCommand.php`

```php
<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    protected static $defaultName = 'build';
    protected static $defaultDescription = 'Build production assets';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Building production assets...');

        // Run production build
        $process = new \Symfony\Component\Process\Process([
            'tailwindcss',
            '-i', 'app/css/app.css',
            '-o', 'app/css/app.bin.css',
            '--minify'
        ]);

        $process->run();

        if ($process->isSuccessful()) {
            $output->writeln('<info>Build completed successfully!</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<error>Build failed:</error>');
        $output->writeln($process->getErrorOutput());
        return Command::FAILURE;
    }
}

return new BuildCommand();
```

**Usage**:
```bash
php wand build
```

The command is automatically registered via the auto-discovery system in `bootstrap/app.php`.

## Command Structure

All commands must:
1. Extend `Symfony\Component\Console\Command\Command`
2. Set `$defaultName` property (command name)
3. Set `$defaultDescription` property (help text)
4. Implement `execute()` method
5. Return a command instance at end of file

**Command Return Codes**:
- `Command::SUCCESS` (0) - Command succeeded
- `Command::FAILURE` (1) - Command failed
- `Command::INVALID` (2) - Invalid usage

## Symfony Console Features

PHP-TW uses Symfony Console, which provides:

- **Input/Output** - Rich CLI formatting with colors and styles
- **Arguments & Options** - Parse command-line parameters
- **Progress Bars** - Visual feedback for long operations
- **Tables** - Formatted tabular output
- **Questions** - Interactive user prompts
- **Signal Handling** - Graceful shutdown on interrupts

**Documentation**: https://symfony.com/doc/current/components/console.html

## Environment Variables

Commands can access environment variables:

```php
$env = $_ENV['APP_ENV'] ?? 'development';
```

Set via shell or `.env` file (requires additional package like `vlucas/phpdotenv`).

## Best Practices

1. **Single Responsibility** - One command per file
2. **Error Handling** - Always handle failures gracefully
3. **User Feedback** - Provide clear output messages
4. **Exit Codes** - Use appropriate return codes
5. **Documentation** - Set clear command descriptions
6. **Validation** - Validate input before execution
