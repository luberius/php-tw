# Post-Install Process

## Overview

PHP-TW uses a post-install script to transform the template package into a ready-to-use project. This document explains how the scaffolding process works.

## Composer Hooks

### Configuration

In `composer.json`:
```json
{
  "scripts": {
    "post-create-project-cmd": [
      "@php post-create-script.php"
    ]
  }
}
```

### Execution Flow

```
User: composer create-project luberius/php-tw my-project
  ↓
Composer:
  1. Clone repository
  2. Run composer install
  3. Execute post-create-project-cmd
  ↓
post-create-script.php:
  1. Copy template files
  2. Display welcome message
  3. Clean up scaffolding files
  ↓
Result: Clean project ready for development
```

## Post-Create Script

**File**: `post-create-script.php`

### Key Functions

#### 1. Recursive Copy

Copies all files from `stubs/default/` to project root:

```php
function copyRecursive($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);

    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            if (is_dir("$src/$file")) {
                copyRecursive("$src/$file", "$dst/$file");
            } else {
                copy("$src/$file", "$dst/$file");
            }
        }
    }

    closedir($dir);
}
```

#### 2. Directory Removal

Recursively removes directories:

```php
function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}
```

#### 3. Welcome Message

Displays setup completion message:

```php
if (file_exists(__DIR__ . '/welcome.php')) {
    require __DIR__ . '/welcome.php';
}
```

### Complete Script Flow

```php
<?php

// 1. Copy template files to project root
copyRecursive(__DIR__ . '/stubs/default', __DIR__);

// 2. Show welcome message
if (file_exists(__DIR__ . '/welcome.php')) {
    require __DIR__ . '/welcome.php';
}

// 3. Clean up temporary files
removeDirectory(__DIR__ . '/stubs');

if (file_exists(__DIR__ . '/welcome.php')) {
    unlink(__DIR__ . '/welcome.php');
}

// 4. Remove self
if (file_exists(__DIR__ . '/post-create-script.php')) {
    unlink(__DIR__ . '/post-create-script.php');
}
```

## Files Copied

### From `stubs/default/`

```
stubs/default/
├── wand                          → wand
├── .gitignore                    → .gitignore
├── app/
│   ├── index.php                 → app/index.php
│   └── css/
│       └── app.css               → app/css/app.css (with v4 @import)
└── bootstrap/
    ├── app.php                   → bootstrap/app.php
    └── commands/
        └── ServeCommand.php      → bootstrap/commands/ServeCommand.php
```

**Note**: No `tailwind.config.js` - Tailwind CSS v4 uses CSS-based configuration via `@theme` directive in `app.css`.

### Generated During Setup

- `app/css/app.bin.css` - Created when `php wand serve` runs for the first time

### Not Copied (Remain in Template)

- `vendor/` - User runs `composer install` if needed
- `composer.json` - Already exists in created project
- `composer.lock` - Already exists in created project

## Welcome Message

**File**: `stubs/default/welcome.php`

```php
<?php

echo "\n";
echo "╔═══════════════════════════════════════╗\n";
echo "║                                       ║\n";
echo "║   Welcome to PHP-TW!                 ║\n";
echo "║                                       ║\n";
echo "╚═══════════════════════════════════════╝\n";
echo "\n";
echo "Next steps:\n";
echo "  1. cd into your project directory\n";
echo "  2. Run: php wand serve\n";
echo "  3. Open: http://127.0.0.1:6969\n";
echo "\n";
```

Displayed after copying files, then deleted.

## File Permissions

The script ensures proper permissions for the `wand` executable:

```php
if (file_exists(__DIR__ . '/wand')) {
    chmod(__DIR__ . '/wand', 0755); // rwxr-xr-x
}
```

This allows `./wand serve` to work on Unix-like systems.

## Customizing Post-Install

### Add Custom Setup Steps

Edit `post-create-script.php`:

```php
// After copying files
copyRecursive(__DIR__ . '/stubs/default', __DIR__);

// Add your custom setup
function customSetup() {
    // Create additional directories
    mkdir(__DIR__ . '/storage/logs', 0755, true);
    mkdir(__DIR__ . '/storage/cache', 0755, true);

    // Create .env file
    copy(__DIR__ . '/.env.example', __DIR__ . '/.env');

    // Add custom Tailwind theme to CSS
    $css = file_get_contents(__DIR__ . '/app/css/app.css');
    $theme = "\n@theme {\n  --color-primary: #3490dc;\n}\n";
    file_put_contents(__DIR__ . '/app/css/app.css', $css . $theme);

    // Set permissions
    chmod(__DIR__ . '/storage', 0755);
}

customSetup();

// Continue with cleanup...
```

### Conditional Setup

```php
// Only create database config if MySQL is available
if (function_exists('mysqli_connect')) {
    copy(__DIR__ . '/stubs/config/database.mysql.php',
         __DIR__ . '/config/database.php');
} else {
    copy(__DIR__ . '/stubs/config/database.sqlite.php',
         __DIR__ . '/config/database.php');
}
```

### Interactive Setup

```php
// Ask user for configuration
echo "Enter your app name: ";
$appName = trim(fgets(STDIN));

echo "Enter primary color (hex): ";
$primaryColor = trim(fgets(STDIN));

// Replace placeholders in .env
$envContent = file_get_contents(__DIR__ . '/.env');
$envContent = str_replace('{{APP_NAME}}', $appName, $envContent);
file_put_contents(__DIR__ . '/.env', $envContent);

// Add theme color to CSS
$css = file_get_contents(__DIR__ . '/app/css/app.css');
$theme = "\n@theme {\n  --color-primary: {$primaryColor};\n}\n";
file_put_contents(__DIR__ . '/app/css/app.css', $css . $theme);
```

## Troubleshooting

### Common Issues

#### Permission Denied

**Problem**: Can't execute `./wand`

**Solution**:
```bash
chmod +x wand
```

#### Files Not Copied

**Problem**: Template files missing after creation

**Solution**: Check `post-create-script.php` for errors:
```bash
php post-create-script.php
```

#### Post-Install Not Running

**Problem**: Script doesn't execute during `composer create-project`

**Solution**: Verify `composer.json`:
```json
{
  "scripts": {
    "post-create-project-cmd": [
      "@php post-create-script.php"
    ]
  }
}
```

#### Tailwind v4 Not Working

**Problem**: CSS not compiling with v4 features

**Solution**: Ensure `app/css/app.css` uses v4 syntax:
```css
@import "tailwindcss";
```

Not the old v3 syntax:
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### Debugging

Add debug output to `post-create-script.php`:

```php
echo "Copying files from stubs/default...\n";
copyRecursive(__DIR__ . '/stubs/default', __DIR__);
echo "Files copied successfully.\n";

echo "Removing stubs directory...\n";
removeDirectory(__DIR__ . '/stubs');
echo "Cleanup complete.\n";
```

## Best Practices

1. **Idempotency** - Script should be safe to run multiple times
2. **Error Handling** - Check if files exist before operations
3. **Clear Output** - Provide feedback during setup
4. **Clean Up** - Remove all temporary files
5. **Documentation** - Document any custom setup steps
6. **Testing** - Test the script thoroughly before release

## Alternative Approaches

### Using Composer Scripts

Instead of a separate PHP file, use Composer scripts:

```json
{
  "scripts": {
    "post-create-project-cmd": [
      "cp -r stubs/default/* .",
      "rm -rf stubs",
      "chmod +x wand",
      "echo 'PHP-TW with Tailwind CSS v4 installed successfully!'"
    ]
  }
}
```

**Pros**: Simpler, no separate file
**Cons**: Less flexibility, platform-specific (Unix/Windows), harder to customize CSS

### Using Composer Plugins

Create a custom Composer plugin for complex setup:

```php
class SetupPlugin implements PluginInterface, EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_CREATE_PROJECT_CMD => 'onPostCreateProject',
        ];
    }

    public function onPostCreateProject(Event $event)
    {
        // Setup logic
    }
}
```

**Pros**: More control, reusable
**Cons**: More complex, requires separate package
