# Development Guide

## Developing PHP-TW Itself

### Clone & Setup

```bash
git clone <repository-url> php-tw
cd php-tw
composer install
```

### Project Structure

```
php-tw/
├── composer.json              # Package configuration
├── post-create-script.php    # Scaffolding script
└── stubs/default/            # Template files (copied to user projects)
    ├── wand                  # CLI entry point
    ├── .gitignore
    ├── app/                  # Web application (includes v4 CSS)
    └── bootstrap/            # CLI framework
```

**Note**: No `tailwind.config.js` in the template - using Tailwind CSS v4 with CSS-based configuration.

### Testing Changes

**Option 1: Test Locally**

```bash
# In php-tw directory
rm -rf test-project
composer create-project luberius/php-tw test-project --repository='{"type":"path","url":"."}'
cd test-project
php wand serve
```

**Option 2: Symlink for Rapid Testing**

```bash
cd php-tw/stubs/default
php wand serve
```

This runs the template directly without creating a project.

### Making Changes

#### 1. Modify Template Files

Edit files in `stubs/default/` - these get copied to user projects.

**Example**: Update welcome message

Edit `stubs/default/welcome.php`:
```php
echo "Welcome to My Custom PHP-TW!\n";
```

#### 2. Add New Commands

Create `stubs/default/bootstrap/commands/YourCommand.php`:

```php
<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class YourCommand extends Command
{
    protected static $defaultName = 'your-command';
    protected static $defaultDescription = 'Description of your command';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Executing your command...');

        // Your logic here

        return Command::SUCCESS;
    }
}

return new YourCommand();
```

#### 3. Modify Post-Install Script

Edit `post-create-script.php` to change scaffolding behavior:

```php
<?php

// Custom post-install logic
function copyRecursive($src, $dst) {
    // Copy logic...
}

// Add custom setup steps
function customSetup() {
    // Your setup code
}

copyRecursive(__DIR__ . '/stubs/default', __DIR__);
customSetup(); // Call your custom setup
```

#### 4. Update Dependencies

Edit `composer.json` to add/update packages:

```json
{
    "require": {
        "symfony/console": "^5.4",
        "your/package": "^1.0"
    }
}
```

Run `composer update` to apply changes.

### Versioning

Update version in `stubs/default/bootstrap/app.php`:

```php
$app = new Application('wand', '2.0.0'); // Update version
```

## Developing Projects Using PHP-TW

### Project Structure

```
my-project/
├── wand                      # CLI tool
├── composer.json
├── app/                      # Your application code
│   ├── index.php
│   └── css/
└── bootstrap/                # Framework code
    ├── app.php
    └── commands/
```

### Adding Custom Commands

Create `bootstrap/commands/TestCommand.php`:

```php
<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected static $defaultName = 'test';
    protected static $defaultDescription = 'Run tests';

    protected function configure(): void
    {
        $this->addArgument('suite', InputArgument::OPTIONAL, 'Test suite to run', 'all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $suite = $input->getArgument('suite');
        $output->writeln("Running {$suite} tests...");

        // Run your tests

        return Command::SUCCESS;
    }
}

return new TestCommand();
```

**Usage**:
```bash
php wand test
php wand test unit
```

### Working with Tailwind CSS v4

#### Custom Theme Configuration

Edit `app/css/app.css` to define your theme using CSS variables:

```css
@import "tailwindcss";

/* Define your custom theme */
@theme {
  /* Colors */
  --color-primary-50: #eff6ff;
  --color-primary-100: #dbeafe;
  --color-primary-500: #3b82f6;
  --color-primary-900: #1e3a8a;

  /* Typography */
  --font-sans: "Inter", system-ui, sans-serif;
  --font-display: "Poppins", sans-serif;

  /* Spacing */
  --spacing-128: 32rem;

  /* Breakpoints */
  --breakpoint-3xl: 1920px;
}

/* Custom utilities using @utility */
@utility text-shadow {
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
}

/* Components using @apply still works */
.btn-primary {
  @apply bg-primary-500 text-white px-4 py-2 rounded hover:bg-primary-600;
}
```

#### Content Detection

Tailwind CSS v4 automatically detects content files - no configuration needed! It scans `./app/**/*.{html,php}` by default.

To explicitly specify content sources (optional):

```css
@import "tailwindcss";
@source "./app/**/*.{html,php}";
@source "./custom-dir/**/*.php";
```

#### Advanced Theme Customization

```css
@import "tailwindcss";

/* Remove default colors and define your own */
@theme {
  --color-*: initial;  /* Remove all default colors */
  --color-white: #fff;
  --color-black: #000;
  --color-brand: oklch(0.84 0.18 117.33);  /* Using OKLCH color space */
}

/* Custom animations */
@theme {
  --animate-slide-in: slide-in 0.3s ease-out;

  @keyframes slide-in {
    from { transform: translateX(-100%); }
    to { transform: translateX(0); }
  }
}
```

### Adding PHP Dependencies

```bash
composer require vendor/package
```

Example: Add a router
```bash
composer require bramus/router
```

Use in `app/index.php`:
```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$router = new \Bramus\Router\Router();

$router->get('/', function() {
    echo 'Home page';
});

$router->get('/about', function() {
    echo 'About page';
});

$router->run();
```

### Environment Configuration

Create `.env` file:
```env
APP_ENV=development
APP_DEBUG=true
DATABASE_URL=mysql://user:pass@localhost/db
```

Install `vlucas/phpdotenv`:
```bash
composer require vlucas/phpdotenv
```

Load in `app/index.php`:
```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$debug = $_ENV['APP_DEBUG'] === 'true';
```

### Database Integration

**Example**: Using PDO

```bash
# No additional package needed, PDO is built-in
```

Create `bootstrap/database.php`:
```php
<?php

return new PDO(
    'mysql:host=localhost;dbname=mydb',
    'username',
    'password',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
```

Use in `app/index.php`:
```php
<?php

$db = require __DIR__ . '/../bootstrap/database.php';

$users = $db->query('SELECT * FROM users')->fetchAll();
```

### Asset Management

**Static Assets**: Place in `app/` directory

```
app/
├── index.php
├── css/
│   └── app.bin.css
├── js/
│   └── app.js
├── images/
│   └── logo.png
└── fonts/
```

Access in HTML:
```html
<link rel="stylesheet" href="css/app.bin.css">
<script src="js/app.js"></script>
<img src="images/logo.png" alt="Logo">
```

### Production Build

PHP-TW includes a built-in `build` command for production optimization:

```bash
php wand build
```

**What it does**:
1. **Minifies CSS** - Creates optimized, production-ready CSS
2. **Optimizes Autoloader** - Generates classmap for faster class loading
3. **Generates OPcache Preload** - Creates preload file for maximum performance
4. **Creates .htaccess** - Adds compression, caching, and security headers
5. **Shows Checklist** - Displays deployment steps

**Output**:
```
🚀 Building for production...

📦 Building minified CSS... ✓
⚡ Optimizing Composer autoloader... ✓
🔥 Generating OPcache preload file... ✓
🔒 Creating production .htaccess... ✓

✅ Production build complete!

📋 Deployment Checklist:
   □ Set APP_ENV=production in .env
   □ Set APP_DEBUG=false in .env
   □ Enable OPcache in php.ini
   □ Set OPcache preload path
   □ Rename .htaccess.production to .htaccess
```

**Performance Benefits**:
- 3-10x faster page loads with OPcache preload
- 30-50% smaller CSS with minification
- Better browser caching with production .htaccess
- Optimized autoloader reduces file system calls

## Environment Configuration

### Using .env File

PHP-TW includes built-in .env support (no dependencies required):

```env
# .env
APP_NAME="My App"
APP_ENV=development
APP_DEBUG=true
DATABASE_URL=mysql://user:pass@localhost/db
```

Access values in your code:

```php
$name = env('APP_NAME');              // "My App"
$debug = env('APP_DEBUG', false);     // true (with default)
$url = env('DATABASE_URL');           // Full connection string

// Boolean conversion
env('APP_DEBUG', false); // Returns boolean true if value is "true"
```

### Environment-Specific Logic

```php
if (env('APP_ENV') === 'production') {
    // Production-only code
    ini_set('display_errors', 0);
} else {
    // Development-only code
    ini_set('display_errors', 1);
}
```

## Debugging

### Error Handling

PHP-TW includes a beautiful development error handler that shows:
- Detailed error messages
- File and line numbers
- Full stack traces
- Syntax-highlighted code

**Enabled when**: `APP_DEBUG=true` in `.env`

**Disabled in production**: Set `APP_DEBUG=false` for production

### Manual Error Display

In `app/index.php` (only if you need custom error settings):
```php
<?php

ini_set('display_errors', env('APP_DEBUG', false) ? '1' : '0');
error_reporting(env('APP_DEBUG', false) ? E_ALL : 0);
```

### Debug Server Command

Add debug output to `bootstrap/commands/ServeCommand.php`:
```php
$output->writeln("Port: $port");
$output->writeln("PHP Server PID: " . $phpProcess->getPid());
$output->writeln("Tailwind PID: " . $tailwindProcess->getPid());
```

### Tailwind Debug

Check which version is installed:
```bash
vendor/bin/tailwindcss-* --help
```

Run Tailwind manually with verbose output:
```bash
vendor/bin/tailwindcss-* -i app/css/app.css -o app/css/app.bin.css --watch
```

Note: The binary name varies by OS (e.g., `tailwindcss-macos-arm64`, `tailwindcss-linux-x64`)

## Hot Reload

PHP-TW automatically reloads the dev server when PHP files change:

```bash
php wand serve
# Edit any .php file in app/
# Server automatically restarts - no manual refresh!
```

**How it works**:
- Monitors `app/` directory for `.php` file changes
- Checks modification times every 100ms
- Automatically restarts PHP server on changes
- Preserves port and Tailwind watcher

**Performance**: Minimal overhead (~0.1% CPU usage for file watching)

## Best Practices

1. **Keep `stubs/default/` clean** - Only essential template files
2. **Version control** - Commit meaningful changes only
3. **Test before release** - Always test with `composer create-project`
4. **Document changes** - Update docs when adding features
5. **Follow PSR** - Use PSR-12 coding standards
6. **Error handling** - Use `APP_DEBUG` to control error display
7. **Environment config** - Use `.env` for all configuration
8. **Security** - Validate user input, escape output
9. **Performance** - Run `php wand build` before deploying
10. **Production** - Always set `APP_DEBUG=false` in production
