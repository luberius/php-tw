<?php

namespace Tests\Unit\Bootstrap\Commands;

use PHPUnit\Framework\TestCase;
use BuildCommand;

class BuildCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../../../stubs/default/bootstrap/commands/BuildCommand.php';
    }

    public function test_it_has_correct_name(): void
    {
        $command = new BuildCommand();

        $this->assertSame('build', $command->getName());
    }

    public function test_it_has_description(): void
    {
        $command = new BuildCommand();

        $description = $command->getDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('production', strtolower($description));
    }

    public function test_it_extends_symfony_command(): void
    {
        $command = new BuildCommand();

        $this->assertInstanceOf(\Symfony\Component\Console\Command\Command::class, $command);
    }
}
