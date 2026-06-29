<?php

namespace Rackbeat\TolgeeSync\Commands;

use Illuminate\Console\Command;

class TolgeeInstall extends Command
{
    protected $signature = 'tolgee:install';

    protected $description = 'Publish GitHub Actions workflow files for Tolgee sync';

    public function handle(): int
    {
//        do {
//            $tolgeeBaseUrl = $this->ask('What is the Tolgee Base URL?');
//            if(!$tolgeeBaseUrl) {
//                $this->error('Tolgee Base URL is required.');
//            }
//        } while(!$tolgeeBaseUrl);
        do {
            $defaultBranch = $this->ask('What is the default branch of the repository?');
            if(!$defaultBranch) {
                $this->error('Default branch is required.');
            }
        } while(!$defaultBranch);
        $languages =
            array_values(array_filter([
                'en',
                $this->confirm('Enable Danish for translations?', false)? 'da' : null,
                $this->confirm('Enable Swedish for translations?', false)? 'sv' : null,
                $this->confirm('Enable Finnish for translations?', false)? 'fi' : null,
                $this->confirm('Enable Norwegian for translations?', false)? 'no' : null,
            ]));

        $force = $this->confirm('Force rebuild existing workflow files?', false);
        $replacements = [
//            '{{ TOLGEE_BASE_URL }}' => $tolgeeBaseUrl,
            '{{ PROJECT_LOCALES }}' => '\'["' . implode('","', $languages) . '"]\'',
            'default-branch' => $defaultBranch,
        ];
        $workflowDir = base_path('.github/workflows');

        if (!is_dir($workflowDir)) {
            if (!mkdir($workflowDir, 0755, true) && !is_dir($workflowDir)) {
                $this->error("Failed to create directory: {$workflowDir}");
                return self::FAILURE;
            }
            $this->line("  <fg=green>✓</> Created directory <fg=cyan>.github/workflows/</>");
        }

        $files = [
            'auto-upload-translation-keys.yml' => 'Upload Translations workflow',
            'sync-tolgee-translations.yml'     => 'Sync Tolgee Translations workflow',
        ];

        foreach ($files as $filename => $description) {
            $destination = $workflowDir . DIRECTORY_SEPARATOR . $filename;
            $stub        = __DIR__ . '/../stubs/' . $filename;

            $workflow = str_replace(
                array_keys($replacements),
                array_values($replacements),
                file_get_contents($stub)
            );

            if (file_exists($destination) && !$force) {
                $this->line("  <fg=yellow>⚠</> Skipped <fg=cyan>.github/workflows/{$filename}</> (already exists, use --force to overwrite)");
                continue;
            }

            if (!file_put_contents($destination, $workflow)) {
                $this->error("Failed to publish workflow file: .github/workflows/{$filename}");
                return self::FAILURE;
            }
            $this->line("  <fg=green>✓</> Created <fg=cyan>.github/workflows/{$filename}</>");
        }

        $this->newLine();
        $this->info('Tolgee workflow files published successfully!');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Add <fg=cyan>TOLGEE_API_TOKEN</> to your GitHub repository secrets');
        $this->line('  2. Add <fg=cyan>COMPOSER_AUTH</> to your GitHub repository secrets');
        $this->line('  3. Update the branch name in <fg=cyan>sync-tolgee-translations.yml</> if needed');

        return self::SUCCESS;
    }
}