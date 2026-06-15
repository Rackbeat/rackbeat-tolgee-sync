<?php

namespace Rackbeat\TolgeeSync\Tests\Unit;

use Rackbeat\TolgeeSync\Exceptions\TolgeeException;
use Rackbeat\TolgeeSync\Tests\TestCase;

class ExceptionTest extends TestCase
{
    /** @test */
    public function it_creates_missing_api_key_exception(): void
    {
        $exception = TolgeeException::missingApiKey();

        $this->assertInstanceOf(TolgeeException::class, $exception);
        $this->assertStringContainsString('API key', $exception->getMessage());
    }

    /** @test */
    public function it_creates_missing_project_exception(): void
    {
        $exception = TolgeeException::missingProject();

        $this->assertInstanceOf(TolgeeException::class, $exception);
        $this->assertStringContainsString('Project ID', $exception->getMessage());
    }

    /** @test */
    public function it_creates_api_request_failed_exception(): void
    {
        $exception = TolgeeException::apiRequestFailed('Connection timeout', 500);

        $this->assertInstanceOf(TolgeeException::class, $exception);
        $this->assertStringContainsString('Connection timeout', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
    }

    /** @test */
    public function it_creates_invalid_configuration_exception(): void
    {
        $exception = TolgeeException::invalidConfiguration('base_url');

        $this->assertInstanceOf(TolgeeException::class, $exception);
        $this->assertStringContainsString('base_url', $exception->getMessage());
    }

    /** @test */
    public function it_creates_translation_file_not_found_exception(): void
    {
        $exception = TolgeeException::translationFileNotFound('auth.php');

        $this->assertInstanceOf(TolgeeException::class, $exception);
        $this->assertStringContainsString('auth.php', $exception->getMessage());
    }

    /** @test */
    public function it_creates_invalid_locale_exception(): void
    {
        $exception = TolgeeException::invalidLocale('xyz');

        $this->assertInstanceOf(TolgeeException::class, $exception);
        $this->assertStringContainsString('xyz', $exception->getMessage());
    }
}

