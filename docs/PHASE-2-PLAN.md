# Phase 2: Interactive Setup with Routing Examples

**Status:** Planning
**Target:** Enhanced developer experience with guided setup
**Timeline:** 4-6 hours implementation
**Priority:** Medium (after Phase 1 completion)

---

## Table of Contents

1. [Overview](#overview)
2. [Goals](#goals)
3. [User Experience](#user-experience)
4. [Features](#features)
5. [Technical Specification](#technical-specification)
6. [Implementation Plan](#implementation-plan)
7. [File Structure](#file-structure)
8. [Testing Strategy](#testing-strategy)
9. [Documentation](#documentation)
10. [Future Enhancements](#future-enhancements)

---

## Overview

Transform the post-create script from a simple file copier into an interactive wizard that:
- Guides users through project setup
- Offers routing approach selection (none/simple/Flight/Slim)
- Optionally includes component examples
- Automatically configures dependencies
- Generates appropriate starter files

**Philosophy:** Stay minimal but helpful. Don't force choices, offer guidance.

---

## Goals

### Primary Goals

1. **Reduce Setup Friction**: Get from `composer create-project` to working app in 60 seconds
2. **Educate Developers**: Show routing options without forcing a framework
3. **Maintain Minimalism**: Keep the barebone philosophy while adding convenience

### Secondary Goals

4. **Showcase Best Practices**: Include well-documented example code
5. **Flexibility**: Support both interactive and non-interactive modes
6. **No Lock-in**: Easy to remove/replace generated code

### Success Metrics

- Setup time: < 2 minutes from creation to first page view
- User satisfaction: Clear understanding of routing choices
- Adoption: 70%+ users try interactive setup instead of skipping

---

## User Experience

### Interactive Flow

```
$ composer create-project luberius/php-tw my-app

[Standard Composer output...]
[Copying files...]

╔════════════════════════════════════════════════╗
║                                                ║
║   Welcome to PHP-TW v1.0                      ║
║   Fast, minimal PHP with Tailwind CSS v4      ║
║                                                ║
╚════════════════════════════════════════════════╝

Let's set up your project! (Press Enter to skip all)

📝 Project name [PHP-TW]: My Awesome App

🛣️  Choose a routing approach:
  [1] None (I'll add my own) ← Default
  [2] Simple switch/case (barebone, ~20 lines)
  [3] Flight PHP (fast micro-framework)
  [4] Slim Framework (PSR-7 compliant)

  Choice [1]: 3

📦 Install Flight PHP? This will:
  • Add mikecao/flight to composer.json
  • Create app/routes/web.php
  • Configure Flight in app/index.php

  Proceed? [Y/n]: y

🎨 Include Tailwind component examples?
  • Pre-built button, card, form components
  • Saved in app/css/components.css

  Include? [y/N]: y

🗄️  Include database examples?
  • PDO connection setup
  • Simple query examples
  • Saved in bootstrap/database.php

  Include? [y/N]: n

⚙️  Installing dependencies...
  ✓ Flight PHP installed
  ✓ Autoloader optimized

✨ Setup complete!

📋 Next steps:
  1. cd my-app
  2. php wand serve
  3. Open http://127.0.0.1:6969

🔗 Docs: docs/getting-started.md
```

### Non-Interactive Mode (CI/Automated)

```bash
# Skip all prompts, use defaults
composer create-project luberius/php-tw my-app --no-interaction

# Or with environment variables
ROUTING=flight COMPONENTS=yes composer create-project luberius/php-tw my-app
```

---

## Features

### 1. Project Name Customization

**Input:**
- User provides project name
- Defaults to "PHP-TW"

**Updates:**
- `.env`: `APP_NAME="User's Project Name"`
- `app/index.php`: Page title
- `README.md` (if generated): Project name

**Implementation:**
```php
$projectName = readline("📝 Project name [PHP-TW]: ") ?: "PHP-TW";
updateEnvFile('APP_NAME', $projectName);
```

---

### 2. Routing Approach Selection

#### Option 1: None (Default)

**What happens:**
- No routing added
- Clean `app/index.php` (current state)
- User builds from scratch

**Files created:**
- None (existing barebone setup)

**Use case:** Developers who want complete control

---

#### Option 2: Simple Switch/Case

**What happens:**
- Add basic router (~20 lines) to `app/index.php`
- No external dependencies
- Pure PHP routing

**Files created:**
```php
// app/index.php (modified)
<?php
require __DIR__ . '/../bootstrap/env.php';
require __DIR__ . '/../bootstrap/error-handler.php';
loadEnv(__DIR__ . '/../.env');

// Simple routing
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

match($uri) {
    '/' => require __DIR__ . '/pages/home.php',
    '/about' => require __DIR__ . '/pages/about.php',
    default => http_response_code(404) && require __DIR__ . '/pages/404.php',
};
```

**Files created:**
- `app/pages/home.php` - Home page with Tailwind
- `app/pages/about.php` - About page example
- `app/pages/404.php` - 404 error page

**Use case:** Simple sites (5-20 pages), learning routing basics

---

#### Option 3: Flight PHP

**What happens:**
- Add `mikecao/flight` to `composer.json`
- Create `app/routes/web.php` with Flight routes
- Configure Flight in `app/index.php`
- Run `composer install`

**Dependencies added:**
```json
{
  "require": {
    "mikecao/flight": "^3.9"
  }
}
```

**Files created:**

**`app/index.php`:**
```php
<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/env.php';
loadEnv(__DIR__ . '/../.env');

Flight::set('flight.views.path', __DIR__ . '/views');

require __DIR__ . '/routes/web.php';

Flight::start();
```

**`app/routes/web.php`:**
```php
<?php

use flight\Engine;
use flight\net\Request;
use flight\net\Response;

// Home page
Flight::route('/', function() {
    Flight::render('home', ['title' => env('APP_NAME')]);
});

// About page
Flight::route('/about', function() {
    Flight::render('about', ['title' => 'About']);
});

// API example
Flight::route('GET /api/status', function() {
    Flight::json(['status' => 'ok', 'version' => '1.0']);
});

// 404 handler
Flight::map('notFound', function() {
    Flight::render('404', [], 'layout');
    http_response_code(404);
});
```

**`app/views/layout.php`:**
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? env('APP_NAME') ?></title>
    <link href="/css/app.bin.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?= $content ?>
</body>
</html>
```

**`app/views/home.php`:**
```php
<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-center mb-8">
        Welcome to <?= env('APP_NAME') ?>
    </h1>
    <p class="text-center text-gray-600">
        Built with PHP-TW + Flight + Tailwind CSS v4
    </p>
</div>
```

**Use case:** APIs, medium-sized apps, need speed + structure

---

#### Option 4: Slim Framework

**What happens:**
- Add `slim/slim` + `slim/psr7` to `composer.json`
- Create `app/routes/web.php` with Slim routes
- Configure Slim with PSR-7 in `app/index.php`
- Run `composer install`

**Dependencies added:**
```json
{
  "require": {
    "slim/slim": "^4.12",
    "slim/psr7": "^1.6"
  }
}
```

**Files created:**

**`app/index.php`:**
```php
<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/env.php';
loadEnv(__DIR__ . '/../.env');

use Slim\Factory\AppFactory;

$app = AppFactory::create();

require __DIR__ . '/routes/web.php';

$app->run();
```

**`app/routes/web.php`:**
```php
<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Home page
$app->get('/', function (Request $request, Response $response) {
    $html = file_get_contents(__DIR__ . '/../views/home.php');
    $response->getBody()->write($html);
    return $response;
});

// About page
$app->get('/about', function (Request $request, Response $response) {
    $html = file_get_contents(__DIR__ . '/../views/about.php');
    $response->getBody()->write($html);
    return $response;
});

// API example
$app->get('/api/status', function (Request $request, Response $response) {
    $payload = json_encode(['status' => 'ok', 'version' => '1.0']);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

// 404 handler
$app->map(['GET', 'POST', 'PUT', 'DELETE'], '/{routes:.+}', function (Request $request, Response $response) {
    $response->getBody()->write('404 Not Found');
    return $response->withStatus(404);
});
```

**Use case:** Enterprise apps, need PSR standards, professional projects

---

### 3. Tailwind Component Examples

**If user selects YES:**

Create `app/css/components.css`:
```css
/* Uncomment to use pre-built components */

/* Buttons */
@utility btn {
  @apply px-4 py-2 rounded font-medium transition-colors cursor-pointer;
}

@utility btn-primary {
  @apply btn bg-blue-500 text-white hover:bg-blue-600;
}

@utility btn-secondary {
  @apply btn bg-gray-500 text-white hover:bg-gray-600;
}

@utility btn-outline {
  @apply btn border-2 border-gray-300 hover:border-gray-400;
}

/* Cards */
@utility card {
  @apply bg-white rounded-lg shadow-md overflow-hidden;
}

@utility card-header {
  @apply px-6 py-4 border-b border-gray-200;
}

@utility card-body {
  @apply px-6 py-4;
}

/* Forms */
@utility input {
  @apply w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500;
}

@utility label {
  @apply block text-sm font-medium text-gray-700 mb-1;
}

/* Alerts */
@utility alert {
  @apply px-4 py-3 rounded border;
}

@utility alert-success {
  @apply alert bg-green-50 border-green-200 text-green-800;
}

@utility alert-error {
  @apply alert bg-red-50 border-red-200 text-red-800;
}
```

Update `app/css/app.css`:
```css
@import "tailwindcss";
@import "components.css";
```

**Files created:**
- `app/css/components.css`

---

### 4. Database Examples

**If user selects YES:**

Create `bootstrap/database.php`:
```php
<?php

/**
 * Database Connection Example
 *
 * This is a simple PDO connection example.
 * Customize the DSN based on your database type.
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = env('DB_HOST', 'localhost');
        $port = env('DB_PORT', '3306');
        $database = env('DB_DATABASE', 'myapp');
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            if (env('APP_DEBUG', false)) {
                throw $e;
            }
            die('Database connection failed');
        }
    }

    return $pdo;
}

/**
 * Execute a query and return all results
 */
function query(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a query and return first result
 */
function queryOne(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

/**
 * Insert a record and return the last insert ID
 */
function insert(string $table, array $data): int
{
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));

    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

    $stmt = db()->prepare($sql);
    $stmt->execute(array_values($data));

    return (int) db()->lastInsertId();
}
```

Create `docs/examples/database.md`:
```markdown
# Database Usage Examples

## Basic Queries

### Select All
```php
require __DIR__ . '/../bootstrap/database.php';

$users = query("SELECT * FROM users");

foreach ($users as $user) {
    echo $user['name'];
}
```

### Select One
```php
$user = queryOne("SELECT * FROM users WHERE id = ?", [1]);

if ($user) {
    echo $user['email'];
}
```

### Insert
```php
$userId = insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'created_at' => date('Y-m-d H:i:s'),
]);
```

### Update
```php
$stmt = db()->prepare("UPDATE users SET name = ? WHERE id = ?");
$stmt->execute(['Jane Doe', 1]);
```

### Delete
```php
$stmt = db()->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([1]);
```

## Using in Routes

### Flight Example
```php
Flight::route('/users', function() {
    require __DIR__ . '/../bootstrap/database.php';

    $users = query("SELECT id, name, email FROM users ORDER BY name");

    Flight::json($users);
});
```

### Slim Example
```php
$app->get('/users', function (Request $request, Response $response) {
    require __DIR__ . '/../bootstrap/database.php';

    $users = query("SELECT id, name, email FROM users ORDER BY name");

    $response->getBody()->write(json_encode($users));
    return $response->withHeader('Content-Type', 'application/json');
});
```
```

**Files created:**
- `bootstrap/database.php`
- `docs/examples/database.md`

---

## Technical Specification

### Post-Create Script Enhancement

**Current flow:**
1. Copy files from `stubs/default/`
2. Create `.env` from `.env.example`
3. Clean up

**New flow:**
1. Detect if interactive mode (TTY available)
2. Show welcome banner
3. **Prompt for project name**
4. **Prompt for routing approach**
5. **Conditional: Prompt for components**
6. **Conditional: Prompt for database examples**
7. Copy files from `stubs/default/`
8. **Apply selected routing template**
9. **Add optional files based on selections**
10. **Update composer.json if dependencies needed**
11. **Run `composer install` if dependencies added**
12. Create `.env` and update `APP_NAME`
13. Clean up
14. **Show next steps**

### Interactive Mode Detection

```php
function isInteractive(): bool
{
    // Check if we're in a TTY
    if (function_exists('posix_isatty')) {
        return posix_isatty(STDIN);
    }

    // Fallback: check if --no-interaction flag
    global $argv;
    return !in_array('--no-interaction', $argv ?? []);
}
```

### User Input Handling

```php
function prompt(string $question, string $default = ''): string
{
    if (!isInteractive()) {
        return $default;
    }

    $prompt = $default ? "{$question} [{$default}]: " : "{$question}: ";
    echo $prompt;

    $input = trim(fgets(STDIN));

    return $input ?: $default;
}

function confirm(string $question, bool $default = true): bool
{
    if (!isInteractive()) {
        return $default;
    }

    $defaultText = $default ? 'Y/n' : 'y/N';
    $prompt = "{$question} [{$defaultText}]: ";
    echo $prompt;

    $input = strtolower(trim(fgets(STDIN)));

    if ($input === '') {
        return $default;
    }

    return in_array($input, ['y', 'yes']);
}

function choice(string $question, array $options, int $default = 1): int
{
    if (!isInteractive()) {
        return $default;
    }

    echo $question . "\n";
    foreach ($options as $i => $option) {
        $num = $i + 1;
        $marker = $num === $default ? '←' : ' ';
        echo "  [{$num}] {$option} {$marker}\n";
    }

    echo "\n  Choice [{$default}]: ";
    $input = trim(fgets(STDIN));

    if ($input === '') {
        return $default;
    }

    $choice = (int) $input;

    if ($choice < 1 || $choice > count($options)) {
        return $default;
    }

    return $choice;
}
```

### Template System

**Directory structure:**
```
stubs/
├── default/              # Base template (always copied)
│   ├── wand
│   ├── .env.example
│   ├── .gitignore
│   ├── app/
│   │   ├── index.php   # Base version (no routing)
│   │   └── css/
│   └── bootstrap/
│       ├── env.php
│       ├── error-handler.php
│       └── commands/
│
└── templates/            # Optional templates
    ├── routing/
    │   ├── simple/
    │   │   ├── index.php
    │   │   └── pages/
    │   │       ├── home.php
    │   │       ├── about.php
    │   │       └── 404.php
    │   ├── flight/
    │   │   ├── index.php
    │   │   ├── routes/
    │   │   │   └── web.php
    │   │   └── views/
    │   │       ├── layout.php
    │   │       ├── home.php
    │   │       └── about.php
    │   └── slim/
    │       ├── index.php
    │       ├── routes/
    │       │   └── web.php
    │       └── views/
    │           ├── home.php
    │           └── about.php
    ├── components/
    │   └── components.css
    └── database/
        ├── database.php
        └── examples/
            └── database.md
```

### Composer JSON Manipulation

```php
function addDependency(string $package, string $version): void
{
    $composerJson = json_decode(file_get_contents('composer.json'), true);

    $composerJson['require'][$package] = $version;

    file_put_contents(
        'composer.json',
        json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
    );
}

function installDependencies(callable $outputCallback = null): void
{
    $callback = $outputCallback ?? function($line) { echo $line; };

    $callback("⚙️  Installing dependencies...\n");

    $process = proc_open(
        'composer install --no-interaction',
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes
    );

    if (is_resource($process)) {
        stream_set_blocking($pipes[1], false);

        while (!feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line) {
                $callback($line);
            }
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);
    }

    $callback("  ✓ Dependencies installed\n");
}
```

---

## Implementation Plan

### Phase 2.1: Core Interactive System (2 hours)

**Tasks:**
1. ✅ Create helper functions (`prompt`, `confirm`, `choice`)
2. ✅ Implement interactive mode detection
3. ✅ Add welcome banner
4. ✅ Implement project name customization
5. ✅ Update `.env` with project name
6. ✅ Test non-interactive mode

**Files to modify:**
- `post-create-script.php`

**Testing:**
- Interactive mode works
- Non-interactive mode uses defaults
- `.env` updated correctly

---

### Phase 2.2: Routing Templates (2 hours)

**Tasks:**
1. ✅ Create simple routing template
2. ✅ Create Flight routing template
3. ✅ Create Slim routing template
4. ✅ Implement routing selection logic
5. ✅ Implement template file copying
6. ✅ Test each routing option

**Files to create:**
- `stubs/templates/routing/simple/*`
- `stubs/templates/routing/flight/*`
- `stubs/templates/routing/slim/*`

**Files to modify:**
- `post-create-script.php`

**Testing:**
- Each routing option creates correct files
- Dependencies added to composer.json when needed
- Routes work correctly (manual testing)

---

### Phase 2.3: Optional Components (1 hour)

**Tasks:**
1. ✅ Create component CSS template
2. ✅ Implement component selection prompt
3. ✅ Copy component file if selected
4. ✅ Update `app.css` import
5. ✅ Test components render correctly

**Files to create:**
- `stubs/templates/components/components.css`

**Files to modify:**
- `post-create-script.php`

**Testing:**
- Components file created when selected
- Import added to `app.css`
- Components work in HTML

---

### Phase 2.4: Database Examples (1 hour)

**Tasks:**
1. ✅ Create database helper functions
2. ✅ Create database documentation
3. ✅ Implement database selection prompt
4. ✅ Copy database files if selected
5. ✅ Test database helpers

**Files to create:**
- `stubs/templates/database/database.php`
- `stubs/templates/database/examples/database.md`

**Files to modify:**
- `post-create-script.php`

**Testing:**
- Database file created when selected
- Documentation copied
- Functions work correctly

---

### Phase 2.5: Documentation & Polish (30 min)

**Tasks:**
1. ✅ Update `docs/getting-started.md` with interactive setup
2. ✅ Create routing examples documentation
3. ✅ Add troubleshooting section
4. ✅ Update README with new features

**Files to modify:**
- `docs/getting-started.md`
- `docs/README.md`
- `README.md` (root)

**Files to create:**
- `docs/examples/routing.md`

---

## File Structure

### After Phase 2 Implementation

```
php-tw/
├── composer.json
├── post-create-script.php          # Enhanced with interactive prompts
├── stubs/
│   ├── default/                     # Base template (always copied)
│   │   ├── wand
│   │   ├── .env.example
│   │   ├── .gitignore
│   │   ├── app/
│   │   │   ├── index.php           # Base (no routing)
│   │   │   └── css/
│   │   │       └── app.css
│   │   └── bootstrap/
│   │       ├── env.php
│   │       ├── error-handler.php
│   │       ├── opcache-preload.php
│   │       └── commands/
│   │           ├── ServeCommand.php
│   │           └── BuildCommand.php
│   │
│   └── templates/                   # Optional templates
│       ├── routing/
│       │   ├── simple/
│       │   │   ├── index.php
│       │   │   └── pages/
│       │   │       ├── home.php
│       │   │       ├── about.php
│       │   │       └── 404.php
│       │   ├── flight/
│       │   │   ├── index.php
│       │   │   ├── routes/
│       │   │   │   └── web.php
│       │   │   └── views/
│       │   │       ├── layout.php
│       │   │       ├── home.php
│       │   │       ├── about.php
│       │   │       └── 404.php
│       │   └── slim/
│       │       ├── index.php
│       │       ├── routes/
│       │       │   └── web.php
│       │       └── views/
│       │           ├── home.php
│       │           ├── about.php
│       │           └── 404.php
│       ├── components/
│       │   └── components.css
│       └── database/
│           ├── database.php
│           └── examples/
│               └── database.md
│
└── docs/
    ├── getting-started.md           # Updated with interactive setup
    ├── examples/
    │   ├── routing.md               # NEW: Routing examples
    │   └── database.md              # NEW: Database examples
    └── PHASE-2-PLAN.md              # This document
```

---

## Testing Strategy

### Unit Tests

**`tests/Unit/PostCreateScriptTest.php`**

```php
public function test_interactive_mode_detection(): void;
public function test_prompt_returns_default_in_non_interactive_mode(): void;
public function test_confirm_returns_default_in_non_interactive_mode(): void;
public function test_choice_returns_default_in_non_interactive_mode(): void;
public function test_add_dependency_updates_composer_json(): void;
public function test_project_name_updates_env_file(): void;
```

### Feature Tests

**`tests/Feature/InteractiveSetupTest.php`**

```php
public function test_simple_routing_creates_correct_files(): void;
public function test_flight_routing_creates_correct_files(): void;
public function test_slim_routing_creates_correct_files(): void;
public function test_components_file_created_when_selected(): void;
public function test_database_helpers_created_when_selected(): void;
public function test_dependencies_installed_when_framework_selected(): void;
```

### Manual Testing Checklist

- [ ] Run interactive setup, select each routing option
- [ ] Verify `php wand serve` works with each routing
- [ ] Test routes work correctly (/, /about, 404)
- [ ] Verify components render in browser
- [ ] Test database helpers (with test database)
- [ ] Run non-interactive mode, verify defaults
- [ ] Test with `--no-interaction` flag

---

## Documentation

### New Documentation Files

1. **`docs/examples/routing.md`**
   - Overview of routing approaches
   - Simple switch/case example
   - Flight PHP example
   - Slim Framework example
   - Comparison table
   - When to use each

2. **`docs/examples/database.md`**
   - PDO setup
   - Helper function usage
   - Query examples
   - Using with routes
   - Best practices

### Updated Documentation

1. **`docs/getting-started.md`**
   - Add "Interactive Setup" section
   - Explain routing choices
   - Document component options

2. **`docs/README.md`**
   - Update features list
   - Add routing examples link

3. **`README.md` (root)**
   - Update quick start
   - Mention interactive setup
   - Add routing options

---

## Future Enhancements

### Phase 3 Ideas (Not Included in Phase 2)

1. **More Templates:**
   - Admin dashboard template
   - Landing page template
   - API-only template

2. **Authentication Scaffolding:**
   - Session-based auth example
   - JWT example
   - OAuth integration

3. **Additional Frameworks:**
   - Lumen support
   - CodeIgniter support

4. **Advanced Database:**
   - Query builder
   - Migration examples
   - Seeder examples

5. **Frontend Build Tools:**
   - Optional Vite integration
   - Alpine.js option
   - HTMX option

6. **Testing Scaffolding:**
   - Generate test files for routes
   - Database testing helpers

7. **Deployment Guides:**
   - Heroku deployment
   - Docker setup
   - Nginx configuration

---

## Success Criteria

Phase 2 is considered complete when:

- ✅ Interactive setup wizard works smoothly
- ✅ All 4 routing options work correctly
- ✅ Components can be added optionally
- ✅ Database helpers can be added optionally
- ✅ Non-interactive mode preserves defaults
- ✅ Documentation is comprehensive
- ✅ Tests cover critical paths
- ✅ Manual testing passes all scenarios

---

## Timeline

**Total Estimated Time: 6 hours**

- Phase 2.1 (Core Interactive): 2 hours
- Phase 2.2 (Routing Templates): 2 hours
- Phase 2.3 (Components): 1 hour
- Phase 2.4 (Database): 1 hour
- Phase 2.5 (Documentation): 30 minutes

**Dependencies:**
- Requires Phase 1 completion (✅ Complete)
- No external dependencies

**Risk Factors:**
- Composer integration complexity: Medium
- Terminal interaction edge cases: Low
- Template file organization: Low

---

## Questions & Decisions

### Resolved

- ✅ **Q:** Should we support Lumen?
  **A:** No, Lumen is archived. Focus on Flight and Slim.

- ✅ **Q:** Inline or separate files for routing?
  **A:** Separate files for maintainability.

- ✅ **Q:** Support both Flight 2 and Flight 3?
  **A:** Flight 3 only (latest stable).

### Pending

- ⏳ **Q:** Should components be enabled by default?
  **A:** TBD - Get user feedback after Phase 2.1

- ⏳ **Q:** Include example API routes by default?
  **A:** TBD - Depends on routing choice popularity

---

## References

- [Flight PHP Documentation](https://docs.flightphp.com/)
- [Slim Framework Documentation](https://www.slimframework.com/docs/)
- [Laravel Testing Best Practices](https://laravel.com/docs/testing)
- [Phase 1 Implementation](../README.md)

---

**Next Steps:** Begin Phase 2.1 implementation after Phase 1 testing is complete.
