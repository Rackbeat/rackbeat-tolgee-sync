<?php

/*
 * This script downloads REVIEWED translations from Tolgee and updates local translation files
 * It should be run from the Laravel project root via: php vendor/rackbeat/rackbeat-tolgee-sync/scripts/syncWithTolgee.php
 */

// Bootstrap Laravel
require __DIR__ . '/../../../autoload.php';
$app = require_once __DIR__ . '/../../../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Rackbeat\TolgeeSync\Builders\TolgeeBuilder;
use Rackbeat\TolgeeSync\LocalKeyManager;

$test = config('rackbeat-tolgee-sync.api_key');
echo $test;
exit(0);

$tolgeeBuilder = new TolgeeBuilder(config('rackbeat-tolgee-sync.api_key'));
$localManager = new LocalKeyManager();
$projects = getProjects($tolgeeBuilder);
$languagesSynced = [];
foreach ($projects as $project) {
    info("Starting sync for project {$project}.");
    $languages = array_filter(
        getLanguages($tolgeeBuilder, $project),
        fn($language) => !in_array($language, $languagesSynced)
    );

    foreach ($languages as $language) {
        $page = 0;
        $end = false;
        info("Getting translations for locale: {$language}");

        while(!$end) {
            $translations = $tolgeeBuilder
                ->translations()
                ->forLanguage($language)
                ->page($page)
                ->take(1000)
                ->get();

            if(property_exists($translations, '_embedded')) {
                unpackAndStore($translations->_embedded, $localManager);
            }

            if(isLastPage($translations)) {
                $end = true;
            }
            $page++;
        }
        info("Languages updated for locale: {$language}");
        $languagesSynced[] = $language;
    }
}

function unpackAndStore($translations, $localManager) {
    $namespacePrefix = config('rackbeat-tolgee-sync.namespace_prefix');
    $skipNamespaces  = config('rackbeat-tolgee-sync.skip_namespaces');
    $syncState       = config('rackbeat-tolgee-sync.sync_state');

    if (empty($namespacePrefix)) {
        echo 'Error: TOLGEE_NAMESPACE_PREFIX is not set. Set it in your workflow:' . PHP_EOL;
        echo '  env:' . PHP_EOL;
        echo '    TOLGEE_NAMESPACE_PREFIX: $(echo \'${{ github.event.repository.name }}\' | tr \'[:upper:]\' \'[:lower:]\')_' . PHP_EOL;
        exit(1);
    }

    foreach ($translations->keys as $translation) {
        if (strpos($translation->keyNamespace, $namespacePrefix) !== 0) {
            continue;
        }

        $file = substr($translation->keyNamespace, strlen($namespacePrefix));

        if (in_array($file, $skipNamespaces)) {
            info("  ⏭ [{$file}] skipped — namespace is in skip_namespaces");
            continue;
        }

        $key = $translation->keyName;
        foreach ($translation->translations as $locale => $translated) {
            if ($translated->text && $translated->state === $syncState) {
                $localManager->storeKey($locale, $file, $key, $translated->text);
                info("  ✓ [{$locale}] {$file}.{$key} = \"{$translated->text}\"");
            } elseif (!$translated->text) {
                info("  ⚠ [{$locale}] {$file}.{$key} skipped — empty translation");
            } else {
                info("  ⚠ [{$locale}] {$file}.{$key} skipped — state is \"{$translated->state}\" (expected \"{$syncState}\")");
            }
        }
    }
}

function info(string $message) {
    echo $message . PHP_EOL;
}

function isLastPage($translations): bool {
    return $translations->page->number >= $translations->page->totalPages - 1;
}

function getLanguages($tolgeeBuilder, $projectId): array {
    $languagesResponse = $tolgeeBuilder->setProject($projectId)->languages()->get();
    if (!isset($languagesResponse->_embedded->languages)) {
        return [];
    }
    return array_map(fn($language) => $language->tag, $languagesResponse->_embedded->languages);
}

function getProjects($tolgeeBuilder): array {
    $projectsResponse = $tolgeeBuilder->projects()->orderBy('id', 'asc')->get();
    if (!isset($projectsResponse->_embedded->projects)) {
        return [];
    }
    return array_map(fn($project) => $project->id, $projectsResponse->_embedded->projects);
}
