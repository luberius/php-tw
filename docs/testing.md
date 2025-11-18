# Testing Guide for PHP-TW

PHP-TW uses PHPUnit for testing, following Laravel's testing best practices.

## Running Tests

### All Tests
```bash
composer test
# or
./vendor/bin/phpunit
```

### Unit Tests Only
```bash
composer test:unit
```

### Feature Tests Only
```bash
composer test:feature
```

### With Code Coverage
```bash
# HTML report
composer test:coverage

# Text report in terminal
composer test:coverage-text
```

### Specific Test File
```bash
./vendor/bin/phpunit tests/Unit/Bootstrap/EnvTest.php
```

### Specific Test Method
```bash
./vendor/bin/phpunit --filter test_it_loads_env_file_successfully
```

### Stop on First Failure
```bash
./vendor/bin/phpunit --stop-on-failure
```

## Test Structure

```
tests/
├── Unit/                           # Isolated unit tests
│   └── Bootstrap/
│       ├── EnvTest.php            # Tests for env.php
│       ├── ErrorHandlerTest.php   # Tests for error-handler.php
│       └── Commands/
│           ├── BuildCommandTest.php
│           └── ServeCommandTest.php
└── Feature/                        # Integration tests
    ├── BuildProductionTest.php
    └── EnvironmentConfigurationTest.php
```

## Writing Tests

### Test Naming Conventions

Use `snake_case` with `test_` prefix:

```php
public function test_it_loads_env_variables_from_file(): void
{
    // Test code
}
```

**Format**: `test_<what>_<expected_behavior>`

**Examples**:
- `test_it_loads_env_file_successfully`
- `test_it_returns_default_when_key_not_found`
- `test_it_converts_string_true_to_boolean`

### Arrange-Act-Assert Pattern

Every test should follow AAA:

```php
public function test_env_returns_default_value(): void
{
    // Arrange - Set up test data
    $_ENV = [];
    putenv('TEST_KEY');

    // Act - Execute the code
    $result = env('TEST_KEY', 'default');

    // Assert - Verify expectations
    $this->assertSame('default', $result);
}
```

### Data Providers

For testing multiple scenarios:

```php
/**
 * @dataProvider envValueProvider
 */
public function test_env_converts_values(string $input, mixed $expected): void
{
    $_ENV['TEST_KEY'] = $input;

    $result = env('TEST_KEY');

    $this->assertSame($expected, $result);
}

public static function envValueProvider(): array
{
    return [
        'true to boolean' => ['true', true],
        'false to boolean' => ['false', false],
        'null to null' => ['null', null],
    ];
}
```

### Setup and Teardown

Always call parent methods:

```php
protected function setUp(): void
{
    parent::setUp();  // MUST be first

    // Your setup code
    $this->tempFile = sys_get_temp_dir() . '/test_' . uniqid();
}

protected function tearDown(): void
{
    // Your cleanup code
    if (file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }

    parent::tearDown();  // MUST be last
}
```

### Testing with Temporary Files

```php
class EnvTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempFile = sys_get_temp_dir() . '/.env.test.' . uniqid();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        parent::tearDown();
    }

    public function test_it_loads_env_file(): void
    {
        file_put_contents($this->tempFile, "KEY=value\n");

        loadEnv($this->tempFile);

        $this->assertSame('value', $_ENV['KEY']);
    }
}
```

## Unit vs Feature Tests

### Unit Tests (`tests/Unit`)

- Test isolated components (single functions/methods)
- Mock external dependencies
- Fast execution
- No framework booting

**Example**: Testing `env()` function

```php
namespace Tests\Unit\Bootstrap;

use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
    public function test_env_returns_value(): void
    {
        $_ENV['KEY'] = 'value';
        $this->assertSame('value', env('KEY'));
    }
}
```

### Feature Tests (`tests/Feature`)

- Test complete workflows
- Integration between components
- Can access full application
- Provides highest confidence

**Example**: Testing build command end-to-end

```php
namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class BuildProductionTest extends TestCase
{
    public function test_build_creates_all_artifacts(): void
    {
        exec('php wand build');

        $this->assertFileExists('app/css/app.bin.css');
        $this->assertFileExists('.htaccess.production');
    }
}
```

## Code Coverage

### Target

Maintain **80% or higher** code coverage.

### Viewing Coverage

```bash
# Generate HTML report
composer test:coverage

# Open in browser
open coverage/index.html
```

### Coverage Requirements

**Critical (100%):**
- `bootstrap/env.php`
- Core command logic

**Important (80%+):**
- Error handling
- File operations
- Build processes

**Optional (<80%):**
- HTML output generation
- Console formatting

## Best Practices

### 1. One Logical Assertion Per Test

```php
// Good - Tests one thing
public function test_env_returns_default_when_not_found(): void
{
    $result = env('NON_EXISTENT', 'default');
    $this->assertSame('default', $result);
}

// Avoid - Tests multiple things
public function test_env_function(): void
{
    $this->assertSame('value', env('KEY'));
    $this->assertSame('default', env('OTHER', 'default'));
    $this->assertTrue(env('BOOL') === true);
}
```

### 2. Use Specific Assertions

```php
// Good - Specific
$this->assertSame('expected', $result);
$this->assertFileExists('/path/to/file');
$this->assertStringContainsString('needle', $haystack);

// Avoid - Generic
$this->assertTrue($result === 'expected');
$this->assertTrue(file_exists('/path/to/file'));
$this->assertTrue(str_contains($haystack, 'needle'));
```

### 3. Test Edge Cases

```php
public function test_it_handles_non_existent_file(): void
{
    loadEnv('/path/does/not/exist');
    // Should not throw exception
    $this->assertTrue(true);
}

public function test_it_handles_empty_file(): void
{
    file_put_contents($this->tempFile, '');
    loadEnv($this->tempFile);
    $this->assertEmpty($_ENV);
}
```

### 4. Clean Up Resources

Always clean up in `tearDown()`:

```php
protected function tearDown(): void
{
    // Delete temp files
    if (file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }

    // Restore environment
    $_ENV = $this->originalEnv;

    parent::tearDown();
}
```

## Common Assertions

```php
// Equality
$this->assertSame($expected, $actual);           // Identical (===)
$this->assertEquals($expected, $actual);         // Equal (==)
$this->assertNotSame($expected, $actual);

// Boolean
$this->assertTrue($condition);
$this->assertFalse($condition);

// Null
$this->assertNull($var);
$this->assertNotNull($var);

// Arrays
$this->assertArrayHasKey('key', $array);
$this->assertArrayNotHasKey('key', $array);
$this->assertCount(5, $array);
$this->assertEmpty($array);

// Strings
$this->assertStringContainsString('needle', $haystack);
$this->assertStringStartsWith('prefix', $string);
$this->assertStringEndsWith('suffix', $string);

// Files
$this->assertFileExists($filename);
$this->assertFileDoesNotExist($filename);
$this->assertDirectoryExists($dirname);

// Types
$this->assertIsString($var);
$this->assertIsInt($var);
$this->assertIsBool($var);
$this->assertIsArray($var);

// Exceptions
$this->expectException(\RuntimeException::class);
$this->expectExceptionMessage('Error message');
```

## Continuous Integration

### GitHub Actions Example

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, pcov

      - name: Install Dependencies
        run: composer install

      - name: Run Tests
        run: composer test

      - name: Code Coverage
        run: composer test:coverage-text
```

## Troubleshooting

### Tests Not Found

Ensure PSR-4 autoloading is configured:

```json
{
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  }
}
```

Run: `composer dump-autoload`

### File Permission Errors

Ensure temp directory is writable:

```php
$tempDir = sys_get_temp_dir() . '/test_' . uniqid();
mkdir($tempDir, 0777, true);
```

### Test Isolation Issues

Always reset state in `tearDown()`:

```php
protected function tearDown(): void
{
    $_ENV = [];
    putenv('KEY');
    parent::tearDown();
}
```

## Additional Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing Docs](https://laravel.com/docs/testing)
- [PHPUnit Best Practices](https://phpunit.readthedocs.io/en/stable/writing-tests-for-phpunit.html)

## Manual Testing

Some features require manual testing due to their interactive or process-based nature.

### Hot Reload (PHP File Watching)

**Purpose**: Verify that the dev server automatically restarts when PHP files change

**Steps**:

1. Start the development server:
   ```bash
   php wand serve
   ```

2. In another terminal, modify a PHP file:
   ```bash
   echo "<!-- Test comment -->" >> app/index.php
   ```

3. **Expected output** in server terminal:
   ```
   🔄 PHP files changed, restarting server...
   ✅ Server restarted successfully
   ```

4. **Verification**:
   - Server restarts within 100-200ms
   - Port remains the same (6969)
   - Tailwind watcher continues running
   - No manual browser refresh needed

**What to test**:
- ✅ Single file changes trigger restart
- ✅ Multiple rapid changes handled gracefully
- ✅ Nested file changes detected
- ✅ Server maintains same port after restart

### Tailwind CSS Watch

**Purpose**: Verify that CSS compiles automatically when Tailwind classes change

**Steps**:

1. Server running from above (or start it):
   ```bash
   php wand serve
   ```

2. Edit the CSS file:
   ```bash
   echo "/* test comment */" >> app/css/app.css
   ```

3. **Expected output**:
   ```
   Rebuilding...
   Done in XXms
   ```

4. **Verification**:
   - `app/css/app.bin.css` is updated
   - File size changes (check with `ls -lh app/css/`)
   - Compilation completes in <300ms
   - Browser auto-refreshes styles (if using browser extensions)

**What to test**:
- ✅ CSS changes trigger rebuild
- ✅ Custom `@theme` definitions compile correctly
- ✅ Invalid CSS shows error message
- ✅ Multiple rapid edits don't cause issues

### Production Build

**Purpose**: Verify that `php wand build` creates all production artifacts

**Steps**:

1. Run the build command:
   ```bash
   php wand build
   ```

2. **Expected output**:
   ```
   🚀 Building for production...

   📦 Building minified CSS... ✓
   ⚡ Optimizing Composer autoloader... ✓
   🔥 Generating OPcache preload file... ✓
   🔒 Creating production .htaccess... ✓

   ✅ Production build complete!
   ```

3. **Verification**:
   ```bash
   # CSS should be minified
   ls -lh app/css/app.bin.css

   # OPcache file should exist
   cat bootstrap/opcache-preload.php | head -20

   # Production .htaccess created
   cat .htaccess.production | grep "mod_deflate"
   ```

**What to check**:
- ✅ CSS file size is smaller than dev version
- ✅ OPcache preload contains opcache_compile_file calls
- ✅ .htaccess.production has compression headers
- ✅ vendor/composer/autoload_classmap.php exists

### Environment Configuration

**Purpose**: Verify that `.env` loading works correctly

**Steps**:

1. Edit `.env` file:
   ```bash
   echo "TEST_VAR=test_value" >> .env
   ```

2. Test in PHP:
   ```bash
   php -r "require 'bootstrap/env.php'; loadEnv('.env'); echo env('TEST_VAR');"
   ```

3. **Expected output**:
   ```
   test_value
   ```

**What to test**:
- ✅ Boolean values (`true`/`false`) converted correctly
- ✅ Null values handled
- ✅ Quoted values unquoted
- ✅ Comments ignored
- ✅ Existing env vars not overridden

### Error Handling

**Purpose**: Verify beautiful error pages in development

**Steps**:

1. Set debug mode:
   ```bash
   # In .env
   APP_DEBUG=true
   ```

2. Create an error in `app/index.php`:
   ```php
   <?php
   require __DIR__ . '/../bootstrap/env.php';
   require __DIR__ . '/../bootstrap/error-handler.php';
   loadEnv(__DIR__ . '/../.env');

   // Trigger error
   trigger_error('Test error', E_USER_ERROR);
   ```

3. Visit `http://127.0.0.1:6969` in browser

4. **Expected result**:
   - Beautiful dark-themed error page
   - Error message: "Test error"
   - File path and line number shown
   - Stack trace visible
   - Syntax highlighting

**What to test**:
- ✅ Errors shown in development (APP_DEBUG=true)
- ✅ Errors hidden in production (APP_DEBUG=false)
- ✅ Exceptions handled
- ✅ Stack trace readable

## Quick Reference

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Run specific test
./vendor/bin/phpunit tests/Unit/Bootstrap/EnvTest.php

# Run specific method
./vendor/bin/phpunit --filter test_it_loads_env

# Stop on first failure
./vendor/bin/phpunit --stop-on-failure

# List all tests
./vendor/bin/phpunit --list-tests
```

---

**Coverage Target**: 80% or higher
**Test Naming**: `snake_case` with `test_` prefix
**Pattern**: Arrange-Act-Assert
**Philosophy**: Test behavior, not implementation
**Manual Tests**: Required for hot reload, file watching, and process management
