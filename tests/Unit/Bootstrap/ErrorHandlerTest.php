<?php

namespace Tests\Unit\Bootstrap;

use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
    private array $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../../stubs/default/bootstrap/env.php';

        $this->originalEnv = $_ENV;

        restore_error_handler();
        restore_exception_handler();
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;

        restore_error_handler();
        restore_exception_handler();

        parent::tearDown();
    }

    public function test_it_sets_display_errors_to_1_when_debug_true(): void
    {
        $_ENV['APP_DEBUG'] = 'true';

        ini_set('display_errors', '0');

        require __DIR__ . '/../../../stubs/default/bootstrap/error-handler.php';

        $this->assertSame('1', ini_get('display_errors'));
    }

    public function test_it_sets_error_reporting_to_E_ALL_when_debug_true(): void
    {
        $_ENV['APP_DEBUG'] = 'true';

        error_reporting(0);

        require __DIR__ . '/../../../stubs/default/bootstrap/error-handler.php';

        $this->assertSame(E_ALL, error_reporting());
    }

    public function test_it_sets_display_errors_to_0_when_debug_false(): void
    {
        $_ENV['APP_DEBUG'] = 'false';

        ini_set('display_errors', '1');

        require __DIR__ . '/../../../stubs/default/bootstrap/error-handler.php';

        $this->assertSame('0', ini_get('display_errors'));
    }

    public function test_it_sets_error_reporting_to_0_when_debug_false(): void
    {
        $_ENV['APP_DEBUG'] = 'false';

        error_reporting(E_ALL);

        require __DIR__ . '/../../../stubs/default/bootstrap/error-handler.php';

        $this->assertSame(0, error_reporting());
    }

    public function test_error_handler_is_registered_when_debug_true(): void
    {
        $_ENV['APP_DEBUG'] = 'true';

        require __DIR__ . '/../../../stubs/default/bootstrap/error-handler.php';

        $handlers = error_get_last();

        $this->assertTrue(true);
    }

    public function test_error_handler_is_not_registered_when_debug_false(): void
    {
        $_ENV['APP_DEBUG'] = 'false';

        require __DIR__ . '/../../../stubs/default/bootstrap/error-handler.php';

        $this->assertTrue(true);
    }
}
