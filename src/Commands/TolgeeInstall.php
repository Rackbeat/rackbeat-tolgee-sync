<?php

namespace Rackbeat\TolgeeSync\Commands;

use Illuminate\Console\Command;

class TolgeeInstall extends Command
{
    protected $signature = 'tolgee:install
                            {tolgee-base-url: Tolgee base URL} 
                            {--force : Overwrite existing workflow files}';

    protected $description = 'Publish GitHub Actions workflow files for Tolgee sync';

    public function handle(): int
    {
        $replacements = [
            '{{TOLGEE_BASE_URL}}' => $this->argument('tolgee-base-url'),
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
                $stub
            );

            if (file_exists($destination) && !$this->option('force')) {
                $this->line("  <fg=yellow>⚠</> Skipped <fg=cyan>.github/workflows/{$filename}</> (already exists, use --force to overwrite)");
                continue;
            }

            if (!is_file($workflow) || !copy($workflow, $destination)) {
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

