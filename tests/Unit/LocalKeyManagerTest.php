<?php

namespace Rackbeat\TolgeeSync\Tests\Unit;

use Rackbeat\TolgeeSync\LocalKeyManager;
use Rackbeat\TolgeeSync\Tests\TestCase;

class LocalKeyManagerTest extends TestCase
{
    protected LocalKeyManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new LocalKeyManager();
    }

    /** @test */
    public function it_formats_simple_array_correctly(): void
    {
        $array = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $result = $this->manager->formatArray($array);

        $this->assertStringContainsString("'key1' => 'value1'", $result);
        $this->assertStringContainsString("'key2' => 'value2'", $result);
    }

    /** @test */
    public function it_formats_nested_array_correctly(): void
    {
        $array = [
            'parent' => [
                'child1' => 'value1',
                'child2' => 'value2',
            ],
        ];

        $result = $this->manager->formatArray($array);

        $this->assertStringContainsString("'parent' => [", $result);
        $this->assertStringContainsString("\t'child1' => 'value1'", $result);
    }

    /** @test */
    public function it_escapes_special_characters_in_values(): void
    {
        $array = [
            'key' => "It's a test",
        ];

        $result = $this->manager->formatArray($array);

        $this->assertStringContainsString("It\\'s a test", $result);
    }

    /** @test */
    public function it_handles_newlines_and_tabs(): void
    {
        $array = [
            'key' => "Line1\nLine2\tTabbed",
        ];

        $result = $this->manager->formatArray($array);

        $this->assertStringContainsString('"', $result);
        $this->assertStringContainsString('\\n', $result);
        $this->assertStringContainsString('\\t', $result);
    }
}

