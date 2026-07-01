<?php

namespace Rackbeat\TolgeeSync\Exceptions;

use Exception;

class TolgeeException extends Exception
{
    public static function missingApiKey(): self
    {
        return new self('Tolgee API key is not configured. Please set TOLGEE_API_TOKEN in your .env file.');
    }

    public static function missingProject(): self
    {
        return new self('Project ID is required but not set. Use setProject() method before making requests.');
    }

    public static function apiRequestFailed(string $message, int $code = 0): self
    {
        return new self("Tolgee API request failed: {$message}", $code);
    }

    public static function invalidConfiguration(string $key): self
    {
        return new self("Invalid or missing configuration: {$key}");
    }

    public static function translationFileNotFound(string $file): self
    {
        return new self("Translation file not found: {$file}");
    }

    public static function invalidLocale(string $locale): self
    {
        return new self("Invalid locale: {$locale}. Please check your configuration.");
    }
}
