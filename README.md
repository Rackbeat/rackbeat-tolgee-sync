# Rackbeat Tolgee Sync

**Private package** for bi-directional synchronization between Laravel translations and Tolgee translation management platform.

## Features

- ✅ Upload translation keys from Laravel to Tolgee
- ✅ Download reviewed translations from Tolgee to Laravel
- ✅ Support for multiple languages (en, no, da, sv)
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

### Upload on Push

Create `.github/workflows/auto-upload-translation-keys.yml` in your project:

```yaml
name: Upload Translations

on:
  push:
    paths:
      - 'resources/lang/**/*'

jobs:
  tolgee-sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - uses: shivammathur/setup-php@v2
        with:
          php-version-file: 'composer.json'

      - name: Install Dependencies
        env:
          COMPOSER_AUTH: '{"github-oauth": {"github.com": "${{secrets.COMPOSER_AUTH}}"} }'
        run: composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

      - name: Upload to Tolgee
        env:
          TOLGEE_API_TOKEN: ${{ secrets.TOLGEE_API_TOKEN }}
          TOLGEE_NAMESPACE_PREFIX: ${{ github.event.repository.name }}_
        run: |
          CHANGED_FILES=$(git diff --name-only origin/staging HEAD | grep '^resources/lang/')
          for file in $CHANGED_FILES; do
            file_name=$(basename "$file")
            php artisan tolgee:update --file="$file_name" --yes-to-all
          done
```

### Download on PR

Create `.github/workflows/sync-tolgee-translations.yml` in your project:

```yaml
name: Sync Tolgee Translations

on:
  pull_request:
    types: ['opened']
    branches: [ "master" ]

jobs:
  sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
        with:
          ref: ${{ github.event.pull_request.head.ref }}

      - uses: shivammathur/setup-php@v2
        with:
          php-version-file: 'composer.json'

      - name: Install Dependencies
        env:
          COMPOSER_AUTH: '{"github-oauth": {"github.com": "${{secrets.COMPOSER_AUTH}}"} }'
        run: composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

      - name: Sync Tolgee Translations
        env:
          TOLGEE_API_TOKEN: ${{ secrets.TOLGEE_API_TOKEN }}
          TOLGEE_NAMESPACE_PREFIX: ${{ github.event.repository.name }}_
        run: php vendor/rackbeat/rackbeat-tolgee-sync/scripts/syncWithTolgee.php

      - name: Commit changes
        run: |
          git config --global user.email "bot@rackbeat.com"
          git config --global user.name "Rackbeat Bot"
          git add .
          git commit -m 'Apply changes from Tolgee' || exit 0
          git push
```

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
    'base_url'         => env('TOLGEE_BASE_URL', 'https://translations.rkbt.io/v2/projects'),
    'base_project'     => env('TOLGEE_BASE_PROJECT', 1),
    'sync_state'       => env('TOLGEE_SYNC_STATE', 'REVIEWED'),
    'api_key'          => env('TOLGEE_API_TOKEN'),
    'namespace_prefix' => env('TOLGEE_NAMESPACE_PREFIX', 'your_repository_name_'), // ⚠️ Set via TOLGEE_NAMESPACE_PREFIX env var. Must end with underscore. In GitHub Actions, use: ${{ github.event.repository.name }}_
    'master_locale'    => env('TOLGEE_MASTER_LOCALE', 'en'),
    'master_folder'    => 'resources/lang',
    'project_locales'  => [
        1 => ['en', 'no', 'da', 'sv'],
    ],
    'skip_namespaces'  => [],
];
```

## License

Proprietary - Rackbeat Internal Use Only

## Support

For issues or questions, contact the Rackbeat development team.
