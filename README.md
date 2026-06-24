# Rackbeat Tolgee Sync

**Private package** for bi-directional synchronization between Laravel translations and Tolgee translation management platform.

## Features

- ✅ Upload translation keys from Laravel to Tolgee
- ✅ Download reviewed translations from Tolgee to Laravel
- ✅ Support for multiple languages (en, no, da, sv, fi)
- ✅ Automatic namespace prefixing
- ✅ GitHub Actions integration
- ✅ Command-line interface

## Installation

### 1. Add the Repository

Add to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:rackbeat/rackbeat-tolgee-sync.git"
        }
    ]
}
```

### 2. Require the Package

```bash
composer require rackbeat/rackbeat-tolgee-sync
```
Before running the command, make sure to:
1. Add `TOLGEE_API_TOKEN` to your GitHub repository secrets - organization secret for now
2. Add `COMPOSER_AUTH` to your GitHub repository secrets - organization secret for now

### 3. Publish GitHub Actions Workflows

Run this command to automatically create both workflow files:

```bash
php artisan tolgee:install
```
Fill in the requested information when prompted (Tolgee Base URL, default branch name, languages and if we allow override of workflow files).
Base URL should be `https://translations.rkbt.io/v2/projects` and default branch name is usually `main`.

This will create:
- `.github/workflows/auto-upload-translation-keys.yml` — uploads changed keys on push
- `.github/workflows/sync-tolgee-translations.yml` — downloads reviewed translations on PR creation with the target branch being the default branch (e.g., `main`)

> Configuration is done via GitHub Secrets and environment variables, so no additional setup is required after installation.

## Usage

### Upload Translations to Tolgee

```bash
# Upload all translation keys
php artisan tolgee:update --yes-to-all

# Upload a specific file
php artisan tolgee:update --file=auth.php --yes-to-all

# Upload interactively (add descriptions, auto-translate, mark as reviewed)
php artisan tolgee:update --file=auth.php
```

### Download Reviewed Translations from Tolgee

```bash
php vendor/rackbeat/rackbeat-tolgee-sync/scripts/syncWithTolgee.php
```

## Available Options

| Option | Type | Description |
|--------|------|-------------|
| `--file` | string | Specific translation file to sync (e.g., `auth.php`) |
| `--yes-to-all` | flag | Skip all interactive prompts (for CI/CD) |
| `--tolgee-token` | string | Override the API token from `.env` |

## GitHub Actions Integration

See [Installation Step 3](#3-publish-github-actions-workflows) above for setup instructions.


### Required GitHub Secrets

| Secret | Description |
|--------|-------------|
| `TOLGEE_API_TOKEN` | Your Tolgee API token from `https://translations.rkbt.io` |
| `COMPOSER_AUTH` | GitHub Personal Access Token with `repo` scope (for installing this private package) |

## Configuration

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=tolgee-config
```

Default configuration:

```php
return [
    'base_url'         => env('TOLGEE_BASE_URL'),
    'base_project'     => env('TOLGEE_BASE_PROJECT', 1),
    'sync_state'       => env('TOLGEE_SYNC_STATE', 'REVIEWED'),
    'api_key'          => env('TOLGEE_API_TOKEN'),
    'namespace_prefix' => env('TOLGEE_NAMESPACE_PREFIX'), // ⚠️ Set via TOLGEE_NAMESPACE_PREFIX env var. Must end with underscore and be lowercase. In GitHub Actions, use: ${{ github.event.repository.name }}_
    'master_locale'    => env('TOLGEE_MASTER_LOCALE', 'en'),
    'master_folder'    => 'resources/lang',
    'project_locales'  =>  1 => env('TOLGEE_PROJECT_LOCALES'),
    'skip_namespaces'  => [],
];
```

## License

Proprietary - Rackbeat Internal Use Only

## Support

For issues or questions, contact the Rackbeat development team.
