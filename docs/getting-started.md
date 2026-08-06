# Getting Started

## Installation

Create a new project using Composer:

```bash
composer create-project luberius/php-tw my-project
cd my-project
```

During installation, the post-create script automatically:
1. Copies template files to your project root
2. Creates `.env` file from `.env.example`
3. Downloads the latest Tailwind CSS v4 binary (on first run)
4. Removes scaffolding files (`stubs/`, `post-create-script.php`)
5. Displays welcome message
6. Prepares your project for development

## Starting the Development Server

```bash
php wand serve
```

This command:
- Finds an available port (default range: 6969-7000)
- Starts PHP built-in server
- Starts Tailwind CSS watcher for live compilation
- Monitors both processes for health

### Server Output

```
Server started on http://127.0.0.1:6969
Press Ctrl+C to stop

🌐 Server running on http://127.0.0.1:6969
🎨 Tailwind CSS watching for changes...
🔄 Hot reload enabled for PHP files...
```

The server automatically:
- Reloads when you edit PHP files
- Recompiles CSS when you edit Tailwind classes
- Watches for changes in real-time

## Project Structure

After creation, your project contains:

```
my-project/
├── wand                      # CLI entry point
├── .env                     # Environment configuration
├── .env.example             # Environment template
├── .gitignore               # Pre-configured ignore rules
├── composer.json            # Dependencies
├── app/
│   ├── index.php           # Application entry point
│   └── css/
│       ├── app.css         # Source CSS (Tailwind v4 @import)
│       └── app.bin.css     # Compiled CSS (auto-generated)
└── bootstrap/
    ├── app.php             # CLI application bootstrapper
    ├── env.php             # Environment loader
    ├── error-handler.php   # Development error handler
    └── commands/
        ├── ServeCommand.php # Development server command
        └── BuildCommand.php # Production build command
```

**Note**: No `tailwind.config.js` file needed! Tailwind CSS v4 uses CSS-based configuration.

## First Steps

### 1. View Your Application

Open `http://127.0.0.1:6969` in your browser. You'll see the default index page.

### 2. Edit the Index Page

Open `app/index.php` and modify the content:

```php
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="css/app.bin.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8">
        <h1 class="text-4xl font-bold text-blue-600">Hello, PHP-TW!</h1>
    </div>
</body>
</html>
```

### 3. Customize Your Theme

Edit `app/css/app.css` to customize Tailwind CSS v4:

```css
@import "tailwindcss";

/* Define custom theme using CSS variables */
@theme {
  --color-primary: #3490dc;
  --color-secondary: #f59e0b;

  --font-display: "Inter", sans-serif;

  --breakpoint-3xl: 1920px;

  --radius-xl: 1rem;
}

/* Custom utilities */
@utility focus-ring {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

/* Regular CSS still works */
body {
  font-family: system-ui, sans-serif;
}
```

Tailwind automatically recompiles to `app/css/app.bin.css` when you save.

**Tailwind CSS v4 Changes:**
- No `tailwind.config.js` file needed
- Configuration is done directly in CSS using `@theme`
- Content detection is automatic (scans `./app/**/*.{html,php}`)
- Much faster build times (3-8x faster than v3)

### 4. Configure Your Environment

Edit `.env` to customize your app:

```env
APP_NAME="My Awesome App"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://127.0.0.1:6969
```

The `.env` file is loaded automatically and values are available via `env()` function:

```php
$appName = env('APP_NAME', 'Default Name');
$debug = env('APP_DEBUG', false);
```

### 5. Build for Production

When ready to deploy:

```bash
php wand build
```

This command:
- Minifies CSS
- Optimizes Composer autoloader
- Generates OPcache preload file
- Creates production `.htaccess`
- Shows deployment checklist

## Error Handling

During development (`APP_DEBUG=true`), you'll see beautiful error pages with:
- Error message and type
- File and line number
- Full stack trace with syntax highlighting

In production (`APP_DEBUG=false`), users see a simple error message while details are hidden.

## Development Reloading

PHP-TW watches PHP files and automatically restarts the development server when they change. Refresh the browser to see PHP changes; Tailwind continues compiling CSS while the server runs.

## Stopping the Server

Press `Ctrl+C` to gracefully stop both the PHP server and Tailwind watcher.

## Next Steps

- [Architecture](architecture.md) - Understand how PHP-TW works
- [CLI Commands](cli-commands.md) - Learn about available commands (including `build`)
- [Development Guide](development.md) - Extend PHP-TW with custom commands
