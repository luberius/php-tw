<?php

namespace Tests\Unit\Bootstrap;

use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
    private string $tempFile;
    private array $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../../stubs/default/bootstrap/env.php';

        $this->tempFile = sys_get_temp_dir() . '/.env.test.' . uniqid();

        $this->originalEnv = $_ENV;
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        $_ENV = $this->originalEnv;

        parent::tearDown();
    }

    public function test_it_loads_env_file_successfully(): void
    {
        file_put_contents($this->tempFile, "TEST_KEY=test_value\n");
        $_ENV = [];

        loadEnv($this->tempFile);

        $this->assertSame('test_value', $_ENV['TEST_KEY']);
        $this->assertSame('test_value', getenv('TEST_KEY'));
    }

    public function test_it_ignores_non_existent_file(): void
    {
        $nonExistentFile = '/path/that/does/not/exist/.env';

        loadEnv($nonExistentFile);

        $this->assertTrue(true);
    }

    public function test_it_skips_comments_and_empty_lines(): void
    {
        $content = "# This is a comment\nKEY1=value1\n\n  \nKEY2=value2\n";
        file_put_contents($this->tempFile, $content);
        $_ENV = [];

        loadEnv($this->tempFile);

        $this->assertArrayNotHasKey('#', $_ENV);
        $this->assertArrayNotHasKey('', $_ENV);
        $this->assertSame('value1', $_ENV['KEY1']);
        $this->assertSame('value2', $_ENV['KEY2']);
    }

    public function test_it_handles_lines_without_equals_sign(): void
    {
        $content = "INVALID_LINE\nVALID_KEY=valid_value\n";
        file_put_contents($this->tempFile, $content);
        $_ENV = [];

        loadEnv($this->tempFile);

        $this->assertArrayNotHasKey('INVALID_LINE', $_ENV);
        $this->assertSame('valid_value', $_ENV['VALID_KEY']);
    }

    public function test_it_trims_quotes_from_values(): void
    {
        $content = "DOUBLE_QUOTED=\"quoted value\"\nSINGLE_QUOTED='quoted value'\n";
        file_put_contents($this->tempFile, $content);
        $_ENV = [];

        loadEnv($this->tempFile);

        $this->assertSame('quoted value', $_ENV['DOUBLE_QUOTED']);
        $this->assertSame('quoted value', $_ENV['SINGLE_QUOTED']);
    }

    public function test_it_does_not_override_existing_env_variables(): void
    {
        $_ENV['EXISTING_KEY'] = 'original_value';
        file_put_contents($this->tempFile, "EXISTING_KEY=new_value\n");

        loadEnv($this->tempFile);

        $this->assertSame('original_value', $_ENV['EXISTING_KEY']);
    }

    public function test_env_function_returns_value_from_env_array(): void
    {
        $_ENV['TEST_KEY'] = 'test_value';

        $result = env('TEST_KEY');

        $this->assertSame('test_value', $result);
    }

    public function test_env_function_returns_value_from_getenv(): void
    {
        putenv('GETENV_KEY=getenv_value');
        unset($_ENV['GETENV_KEY']);

        $result = env('GETENV_KEY');

        $this->assertSame('getenv_value', $result);
    }

    public function test_env_returns_default_when_key_not_found(): void
    {
        unset($_ENV['NON_EXISTENT']);
        putenv('NON_EXISTENT');

        $result = env('NON_EXISTENT', 'default_value');

        $this->assertSame('default_value', $result);
    }

    /**
     * @dataProvider envValueProvider
     */
    public function test_env_converts_special_values(string $input, mixed $expected): void
    {
        $_ENV['TEST_KEY'] = $input;

        $result = env('TEST_KEY');

        $this->assertSame($expected, $result);
    }

    public static function envValueProvider(): array
    {
        return [
            'true string to boolean' => ['true', true],
            'false string to boolean' => ['false', false],
            'null string to null' => ['null', null],
            'parenthesized true' => ['(true)', true],
            'parenthesized false' => ['(false)', false],
            'parenthesized null' => ['(null)', null],
            'regular string unchanged' => ['hello', 'hello'],
            'numeric string unchanged' => ['123', '123'],
            'empty string unchanged' => ['', ''],
        ];
    }

    public function test_it_handles_multiline_values_by_taking_first_line(): void
    {
        $content = "KEY=value1=value2\n";
        file_put_contents($this->tempFile, $content);
        $_ENV = [];

        loadEnv($this->tempFile);

        $this->assertSame('value1=value2', $_ENV['KEY']);
    }
}
