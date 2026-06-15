<?php

namespace Rackbeat\TolgeeSync\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Rackbeat\TolgeeSync\Tests\TestCase;

class TolgeeSyncCommandTest extends TestCase
{
    /** @test */
    public function it_registers_tolgee_update_command(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('tolgee:update', $commands);
    }

    /** @test */
    public function it_has_correct_command_signature(): void
    {
        $command = Artisan::all()['tolgee:update'];

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('file'));
        $this->assertTrue($definition->hasOption('yes-to-all'));
        $this->assertTrue($definition->hasOption('tolgee-token'));
    }
}

