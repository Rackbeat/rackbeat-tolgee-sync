<?php

namespace Rackbeat\TolgeeSync\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Rackbeat\TolgeeSync\Builders\TolgeeBuilder;

class TolgeeSync extends Command
{
    protected $signature = 'tolgee:update {--file=} {--yes-to-all} {--tolgee-token=}';

    protected $description = 'Add translation keys from local lang files to Tolgee';

    protected array $projectLocales;
    protected string $masterLocale;
    protected string $masterFolder;
    protected ?string $namespacePrefix;
    protected string $file = '*.php';

    public function __construct()
    {
        parent::__construct();
        $this->projectLocales = [
            1 => array_values(json_decode(trim(config('rackbeat-tolgee-sync.project_locales'), "'"), true) ?? []),
        ];
        $this->masterLocale = config('rackbeat-tolgee-sync.master_locale');
        $this->masterFolder = config('rackbeat-tolgee-sync.master_folder');
        $this->namespacePrefix = config('rackbeat-tolgee-sync.namespace_prefix');
    }

    public function handle()
    {
        if (empty($this->namespacePrefix)) {
            $this->error('TOLGEE_NAMESPACE_PREFIX is not set. Set it via the environment variable or in your workflow:');
            $this->line('  env:');
            $this->line("    TOLGEE_NAMESPACE_PREFIX: \$(echo '\${{ github.event.repository.name }}' | tr '[:upper:]' '[:lower:]')_");
            return 1;
        }

        $project = config('rackbeat-tolgee-sync.base_project');
        if (!isset($this->projectLocales[$project])) {
            $this->error("Invalid tolgee.base_project [{$project}]");
            throw new \RuntimeException("Invalid tolgee.base_project [{$project}]");
        }
        if (!is_array($this->projectLocales[$project])) {
            $this->error("Missing tolgee.project_locales mapping.");
            throw new \RuntimeException("Missing tolgee.project_locales mapping.");
        }
        $locales = $this->projectLocales[$project];

        $this->info('Scanning for new keys...');
        $this->line('Locales to include: <fg=cyan>' . implode(', ', $locales) . '</>');
        $this->newLine();

        $masterKeys = $this->getMasterKeys();
        $created = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($masterKeys as $masterKey) {
            $tolgeeBuilder = $this->getTolgeeBuilder();
            $data = $this->prepareData($masterKey);
            dd($data);

            try {
                $response = $tolgeeBuilder->keys()->post($data);

                if ($this->translationCreated($response)) {
                    $created++;
                    $this->line("  <fg=green>✓</> Created  [{$masterKey}]");
                } else {
                    $skipped++;
                    $errorMessage = isset($response->message) ? $response->message : json_encode($response);
                    $this->warn("  ⚠ Skipped [{$masterKey}]: {$errorMessage}");
                }
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'key_exists')) {
                    $skipped++;
                    $this->line("  <fg=yellow>⏭</> Exists   [{$masterKey}]");
                } else {
                    $failed++;
                    $this->error("  ✗ Failed  [{$masterKey}]: " . $e->getMessage());
                }
            }

            usleep(250);
        }

        $this->newLine();
        $this->info("Done — Created: {$created} | Skipped (exists): {$skipped} | Failed: {$failed}");
    }

    protected function getTolgeeBuilder(): TolgeeBuilder
    {
        // Use ?: instead of ?? to also catch empty string tokens
        $apiKey = $this->option('tolgee-token') ?: config('rackbeat-tolgee-sync.api_key');
        return (new TolgeeBuilder($apiKey))->setProject(config('rackbeat-tolgee-sync.base_project'));
    }

    public function translationCreated($response): bool
    {
        return property_exists($response, 'translations');
    }

    public function getTranslationKeys(array $array, string $parentKey = ''): array
    {
        $keys = [];
        foreach ($array as $key => $value) {
            $currentKey = empty($parentKey) ? $key : $parentKey . '.' . $key;

            if (is_array($value)) {
                $keys = array_merge($keys, $this->getTranslationKeys($value, $currentKey));
            } else {
                $keys[] = $currentKey;
            }
        }

        return $keys;
    }

    public function getMasterKeys(): array
    {
        $masterKeys = [];
        $masterFolder = $this->getMasterFolder();

        foreach (glob($masterFolder . $this->getFile(), GLOB_NOSORT) as $masterFile) {
            $file = include $masterFolder . basename($masterFile);
            $masterKeys = array_merge($masterKeys, $this->getTranslationKeys($file, pathinfo($masterFile, PATHINFO_FILENAME)));
        }

        $masterKeys = array_map(function ($key) {
            return $this->namespacePrefix . $key;
        }, $masterKeys);

        return $masterKeys;
    }

    public function getTranslation(string $key, string $locale)
    {
        $key = Str::after($key, $this->namespacePrefix);
        return Lang::hasForLocale($key, $locale) ? trans($key, [], $locale) : '';
    }

    public function markTranslationsAsReviewed($response, $tolgeeBuilder)
    {
        foreach ((array) $response->translations as $translation) {
            if ($translation === '') {
                continue;
            }
            $tolgeeBuilder->translations()->find($translation->id)->setTranslationState('REVIEWED')->put();
        }
    }

    public function autotranslateKey($response, $tolgeeBuilder)
    {
        $tolgeeBuilder->keys()->find($response->id)->autoTranslate(['da', 'fi', 'no', 'sv', 'de'])->put();
        $this->markKeyAsAutotranslated($response, $tolgeeBuilder);
    }

    public function markKeyAsAutotranslated($response, $tolgeeBuilder): void
    {
        $tolgeeBuilder->keys()->find($response->id)->tags()->put(['name' => 'MACHINE TRANSLATED']);
    }

    public function addDescriptionForKey($response, string $description, $tolgeeBuilder)
    {
        $locale = $this->masterLocale;
        $translationId = $response->translations->{$locale}->id ?? null;
        if (!$translationId) {
            return;
        }
        $tolgeeBuilder->translations()->find($translationId)->comments()->post([
            'text' => $description,
            'state' => 'RESOLUTION_NOT_NEEDED',
        ]);
    }

    public function getFile()
    {
        return $this->option('file') ?? $this->file;
    }

    public function getTag(): string
    {
        return date('Y-m-d');
    }

    public function getMasterFolder(): string
    {
        return base_path($this->masterFolder . DIRECTORY_SEPARATOR . $this->masterLocale . DIRECTORY_SEPARATOR);
    }

    public function prepareData(string $masterKey, int $project = null): array
    {
        if ($project === null) {
            $project = config('rackbeat-tolgee-sync.base_project');
            if (!isset($this->projectLocales[$project]) || !is_array($this->projectLocales[$project])) {
                $this->error("Invalid tolgee.base_project [{$project}] or missing tolgee.project_locales mapping.");
                throw new \RuntimeException("Invalid tolgee.base_project [{$project}] or missing tolgee.project_locales mapping.");
            }
        }

        $parts = explode('.', $masterKey);
        $namespace = array_shift($parts);
        $key = implode('.', $parts);

        return [
            'name' => $key,
            'namespace' => $namespace,
            'tags' => [$this->getTag()],
            'translations' => $this->prepareTranslations($masterKey, $project),
        ];
    }

    public function prepareTranslations(string $masterKey, int $project): array
    {
        dd($this->projectLocales);
        if (!isset($this->projectLocales[$project]) || !is_array($this->projectLocales[$project])) {
            return [];
        }
        $translations = [];
        foreach ($this->projectLocales[$project] as $locale) {
            $translations[$locale] = $this->getTranslation($masterKey, $locale);
        }

        return $translations;
    }

    public function continueWithTranslateAndContext($response, $masterKey, $tolgeeBuilder)
    {
        if ($this->option('yes-to-all')) {
            return;
        }

        if ($description = $this->ask(PHP_EOL . "Please provide description for the translation: ({$masterKey}) " . $this->getTranslation($masterKey, $this->masterLocale))) {
            $this->addDescriptionForKey($response, $description, $tolgeeBuilder);
        }
        if ($this->confirm('Do you want to auto translate the key ' . $this->getTranslation($masterKey, $this->masterLocale) . '?')) {
            $this->autotranslateKey($response, $tolgeeBuilder);
        }
        if ($this->confirm('Should the translations for ' . $this->getTranslation($masterKey, $this->masterLocale) . ' be marked as reviewed?')) {
            $this->markTranslationsAsReviewed($response, $tolgeeBuilder);
        }
    }
}

