# Architecture

## Overview

PHP-TW uses a **template-based scaffolding** approach where Composer creates projects from a pre-configured template, then a post-install script customizes the generated project.

## Core Components

### 1. Template Distribution (`stubs/default/`)

The `stubs/default/` directory contains the template files that get copied to user projects:

- **wand** - CLI executable (#!/usr/bin/env php)
- **bootstrap/** - Application framework
- **app/** - Web application files (includes CSS with v4 @import)
- **.gitignore** - Version control rules

**Note**: No `tailwind.config.js` file - Tailwind CSS v4 uses CSS-based configuration via `@theme` directive.

### 2. Post-Install Scaffolding

**File**: `post-create-script.php`

**Triggered by**: `composer.json` → `post-create-project-cmd`

**Process**:
```php
1. Copy stubs/default/* to project root
2. Display welcome.php message
3. Remove temporary files:
   - stubs/
   - welcome.php
   - post-create-script.php itself
```

This ensures users get a clean project without scaffolding artifacts.

### 3. CLI Framework

**Entry Point**: `wand` (executable script)

```php
#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->run();
```

**Bootstrapper**: `bootstrap/app.php`

```php
use Symfony\Component\Console\Application;

$app = new Application('wand', '1.0.0');

// Auto-discover and register commands
foreach (glob(__DIR__ . '/commands/*.php') as $file) {
    $command = require $file;
    $app->add($command);
}

return $app;
```

**Design Pattern**: Auto-discovery pattern - commands are loaded dynamically without manual registration.

### 4. Development Server

**File**: `bootstrap/commands/ServeCommand.php`

**Architecture**:

```
ServeCommand::execute()
│
├─> Port Discovery (findAvailablePort)
│   └─> Tests ports 6969-7000 using socket_create/fsockopen
│
├─> Process 1: PHP Built-in Server
│   └─> php -S 127.0.0.1:PORT -t app
│
├─> Process 2: Tailwind CSS Watcher
│   └─> tailwindcss -i app/css/app.css -o app/css/app.bin.css --watch
│
└─> Signal Handling (SIGINT)
    └─> Gracefully terminate both processes on Ctrl+C
```

**Process Management Strategy**:
- Uses Symfony `Process` component
- Runs processes in background mode (`start()` vs `run()`)
- Polls process status in monitoring loop
- Outputs stdout/stderr in real-time

### 5. Tailwind CSS Integration

**Package**: `luberius/tailwindcss`

**Strategy**: Platform-specific binary abstraction

```
Tailwind CSS Wrapper
│
├─> Binary Selection (based on PHP_OS/arch)
│   ├─> Darwin (macOS): arm64, x86_64
│   ├─> Linux: x86_64, aarch64
│   └─> Windows: x86_64
│
├─> Download & Cache (from /releases/latest/download/)
│   └─> sys_get_temp_dir() + 'tailwindcss-cache'
│
└─> Execution
    └─> Passthrough to Tailwind CLI with arguments
```

**Benefits**:
- No Node.js dependency
- Auto-downloads latest Tailwind CSS (v4.x)
- Cached for subsequent runs
- Cross-platform compatibility
- Zero configuration required

## Data Flow

### Project Creation Flow

```
User: composer create-project luberius/php-tw my-project
  ↓
Composer: Downloads package from GitHub/Packagist
  ↓
Composer: Executes post-create-project-cmd
  ↓
post-create-script.php:
  ├─> Copy stubs/default/* to ./
  ├─> Display welcome message
  └─> Self-destruct (delete stubs/, welcome.php, itself)
  ↓
User: Ready-to-use project in my-project/
```

### Development Flow

```
User: php wand serve
  ↓
wand script: Load bootstrap/app.php
  ↓
bootstrap/app.php:
  ├─> Create Symfony Application
  ├─> Auto-discover commands/*.php
  └─> Return application instance
  ↓
Symfony Console: Route to ServeCommand
  ↓
ServeCommand::execute():
  ├─> Find available port
  ├─> Start PHP server (Process 1)
  ├─> Start Tailwind watcher (Process 2)
  └─> Monitor both processes
  ↓
Browser: http://127.0.0.1:6969
  ├─> PHP server serves app/index.php
  └─> CSS loaded from app/css/app.bin.css
  ↓
User: Edit app/css/app.css or app/index.php
  ↓
Tailwind watcher: Detects changes → Recompile CSS
  ↓
Browser: Refresh → See changes
```

## Design Patterns

### 1. Template Method Pattern
- `post-create-script.php` defines the scaffolding algorithm
- Users can customize template files in `stubs/default/`

### 2. Command Pattern
- Each CLI command is a separate class extending Symfony Command
- Commands are encapsulated units of functionality

### 3. Auto-Discovery Pattern
- Commands are discovered via filesystem scanning
- No manual registration required

### 4. Process Orchestration
- ServeCommand coordinates multiple concurrent processes
- Handles lifecycle management (start, monitor, stop)

### 5. Strategy Pattern
- Tailwind wrapper selects binary strategy based on OS/architecture
- Abstracts platform differences behind unified interface

## Dependency Graph

```
php-tw (template package)
├── symfony/console (^5.4)
│   └── symfony/process (^5.4)
└── luberius/tailwindcss (^1.0)
    └── Platform-specific Tailwind CLI binaries
```

## File Responsibilities

| File | Responsibility | Pattern |
|------|---------------|---------|
| `composer.json` | Package metadata, dependencies, scripts | Configuration |
| `post-create-script.php` | Project scaffolding | Template Method |
| `stubs/default/wand` | CLI entry point | Front Controller |
| `bootstrap/app.php` | Application bootstrap, command discovery | Auto-Discovery |
| `bootstrap/commands/ServeCommand.php` | Dev server orchestration | Command, Process Orchestration |
| `app/index.php` | Web application entry | Front Controller |
| `app/css/app.css` | Tailwind v4 configuration (CSS-based) | Configuration |

## Extension Points

PHP-TW is designed to be extended:

1. **Add Custom Commands**: Create files in `bootstrap/commands/`
2. **Modify Template**: Edit files in `stubs/default/`
3. **Customize Post-Install**: Modify `post-create-script.php`
4. **Customize Tailwind Theme**: Use `@theme` directive in `app/css/app.css`
5. **Add Dependencies**: Update `composer.json` in template

See [Development Guide](development.md) for implementation details.
