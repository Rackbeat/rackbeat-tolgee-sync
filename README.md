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

Add the repository to your project's `composer.json`:

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

The package will auto-register via Laravel's package discovery.

### 3. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=tolgee-config
```

### 4. Environment Variables

Add to your `.env`:

```env
TOLGEE_API_KEY=your-api-key-here
TOLGEE_BASE_URL=https://translations.rkbt.io/v2/projects
TOLGEE_BASE_PROJECT=1
TOLGEE_NAMESPACE_PREFIX=b2b_
TOLGEE_MASTER_LOCALE=en
TOLGEE_SYNC_STATE=REVIEWED
```

## Usage

### Upload Translations to Tolgee

Upload all translation keys:
```bash
php artisan tolgee:update --yes-to-all
```

Upload a specific file:
```bash
php artisan tolgee:update --file=auth.php --yes-to-all
```

Upload with custom API token:
```bash
php artisan tolgee:update --file=auth.php --tolgee-token=your-token
```

Upload interactively (add descriptions, auto-translate, mark as reviewed):
```bash
php artisan tolgee:update --file=auth.php
```

### Download Reviewed Translations from Tolgee

Run the sync script (typically used in GitHub Actions):

```bash
php vendor/rackbeat/rackbeat-tolgee-sync/scripts/syncWithTolgee.php
```

This will:
1. Fetch all projects from Tolgee
2. For each language, download all REVIEWED translations
3. Update local PHP translation files

## Commands & Options

### `php artisan tolgee:update`

Uploads translation keys from your local Laravel translation files to Tolgee.

#### Available Options

| Option | Type | Description | Required |
|--------|------|-------------|----------|
| `--file` | string | Specific translation file to sync (e.g., `auth.php`) | No |
| `--yes-to-all` | flag | Skip all interactive prompts (for CI/CD) | No |
| `--tolgee-token` | string | Custom Tolgee API token (overrides .env) | No |

#### Command Modes

**🤖 Automated Mode (CI/CD)**
```bash
php artisan tolgee:update --yes-to-all
```
- Uploads all translation keys
- No prompts for descriptions, auto-translate, or review
- Best for GitHub Actions workflows

**📁 Single File Mode**
```bash
php artisan tolgee:update --file=auth.php --yes-to-all
```
- Uploads only keys from `resources/lang/en/auth.php`
- Includes translations for all configured locales (en, no, da, sv)
- Faster than uploading all files

**💬 Interactive Mode**
```bash
php artisan tolgee:update --file=users.php
```
- Prompts for each key:
  - Add description/context for translators
  - Auto-translate to other languages (da, fi, no, sv, de)
  - Mark translations as "REVIEWED"
- Best for new features with context

**🔑 Custom Token Mode**
```bash
php artisan tolgee:update --file=products.php --tolgee-token=custom_token_here
```
- Uses a different API token than configured in `.env`
- Useful for multi-project setups

#### What Gets Uploaded

When you run the command, it:
1. ✅ Scans `resources/lang/en/` for translation keys
2. ✅ Reads translations from `da`, `no`, `sv` locales
3. ✅ Adds namespace prefix (e.g., `b2b_auth.login`)
4. ✅ Tags with current date (e.g., `2026-06-12`)
5. ✅ Posts to Tolgee with all language translations

#### Example Workflow

```bash
# 1. You add a new key to resources/lang/en/auth.php
'logout' => 'Logout'

# 2. Add corresponding translations (optional, can be filled via Tolgee)
# resources/lang/da/auth.php: 'logout' => 'Log ud'
# resources/lang/no/auth.php: 'logout' => 'Logg ut'

# 3. Upload to Tolgee
php artisan tolgee:update --file=auth.php --yes-to-all

# 4. Key 'b2b_auth.logout' is created in Tolgee with all translations
```

### `syncWithTolgee.php` Script

Downloads REVIEWED translations from Tolgee and updates local files.

#### Usage

```bash
php vendor/rackbeat/rackbeat-tolgee-sync/scripts/syncWithTolgee.php
```

#### What It Does

1. 🔍 Fetches all Tolgee projects
2. 🌍 Gets all configured languages
3. 📥 Downloads translations in batches (1000 keys per page)
4. ✅ Filters only REVIEWED translations
5. 📝 Updates `resources/lang/{locale}/{file}.php` files
6. 🎨 Preserves file formatting and structure

#### Filtering

- Only syncs keys with namespace prefix (e.g., `b2b_*`)
- Only syncs translations with state = `REVIEWED`
- Skips namespaces in `skip_namespaces` config

#### Output Example

```
Starting sync for project 1.
Getting translations for locale: en
Getting translations for locale: da
Languages updated for locale: da
Getting translations for locale: no
Languages updated for locale: no
Getting translations for locale: sv
Languages updated for locale: sv
```

## GitHub Actions Integration

### Upload on Push

In your main project, create `.github/workflows/auto-upload-translation-keys.yml`:

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
          php-version: '8.1'
      
      - name: Install Dependencies
        env:
          COMPOSER_AUTH: '{"github-oauth": {"github.com": "${{secrets.COMPOSER_AUTH}}"} }'
        run: composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs
      
      - name: Upload to Tolgee
        run: |
          CHANGED_FILES=$(git diff --name-only origin/staging HEAD | grep '^resources/lang/')
          for file in $CHANGED_FILES; do
            file_name=$(basename "$file")
            php artisan tolgee:update --file="$file_name" --yes-to-all --tolgee-token="${{ secrets.TOLGEE_API_KEY }}"
          done
```

### Download on PR

Create `.github/workflows/sync-tolgee-translations.yml`:

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
          php-version: '8.1'
      
      - name: Install Dependencies
        env:
          COMPOSER_AUTH: '{"github-oauth": {"github.com": "${{secrets.COMPOSER_AUTH}}"} }'
        run: composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs
      
      - name: Sync Tolgee Translations
        env:
          TOLGEE_API_KEY: ${{ secrets.TOLGEE_API_KEY }}
        run: php vendor/rackbeat/rackbeat-tolgee-sync/scripts/syncWithTolgee.php
      
      - name: Commit changes
        run: |
          git config --global user.email "bot@rackbeat.com"
          git config --global user.name "Rackbeat Bot"
          git add .
          git commit -m 'Apply changes from Tolgee' || exit 0
          git push
```

## GitHub Secrets Setup

For the GitHub Actions workflows to work, you need to add the following secrets to your repository:

### 1. Navigate to Repository Settings

Go to your GitHub repository → **Settings** → **Secrets and variables** → **Actions**

### 2. Add Required Secrets

Click **"New repository secret"** and add:

#### `TOLGEE_API_KEY`
- **Name:** `TOLGEE_API_KEY`
- **Value:** Your Tolgee API key from `https://translations.rkbt.io`
- **Used for:** Authenticating with Tolgee to upload/download translations

#### `COMPOSER_AUTH`
- **Name:** `COMPOSER_AUTH`
- **Value:** GitHub Personal Access Token with `repo` or `read:packages` scope
- **Used for:** Installing private Composer packages during CI/CD

### 3. How to Get Your Tolgee API Key

1. Log into Tolgee at `https://translations.rkbt.io`
2. Navigate to your project settings
3. Go to **API keys** or **Integration** section
4. Copy the API key

### 4. How to Create COMPOSER_AUTH Token

1. Go to GitHub → Your profile → **Settings**
2. Navigate to **Developer settings** → **Personal access tokens** → **Tokens (classic)**
3. Click **"Generate new token (classic)"**
4. Name it: `Composer CI/CD Access`
5. Select scope: **`repo`** (Full control of private repositories)
6. Click **"Generate token"**
7. Copy the token and add it as the `COMPOSER_AUTH` secret

### Notes
- The `GITHUB_TOKEN` is automatically provided by GitHub Actions, no setup needed
- Never commit these secrets to your repository
- Secrets are encrypted and only exposed to GitHub Actions runners

## Configuration

### Default Configuration

```php
return [
    'base_url' => env('TOLGEE_BASE_URL', 'https://translations.rkbt.io/v2/projects'),
    'base_project' => env('TOLGEE_BASE_PROJECT', 1),
    'sync_state' => env('TOLGEE_SYNC_STATE', 'REVIEWED'),
    'api_key' => env('TOLGEE_API_KEY'),
    'namespace_prefix' => env('TOLGEE_NAMESPACE_PREFIX', 'b2b_'),
    'master_locale' => env('TOLGEE_MASTER_LOCALE', 'en'),
    'master_folder' => 'resources/lang',
    'project_locales' => [
        1 => ['en', 'no', 'da', 'sv'],
    ],
    'skip_namespaces' => [],
];
```

### Customization

Publish and modify the config file:

```bash
php artisan vendor:publish --tag=tolgee-config
```

Then edit `config/tolgee.php` as needed.

## How It Works

### Upload Flow
1. Reads translation keys from `resources/lang/en/` (master locale)
2. Adds namespace prefix (`b2b_` by default)
3. Fetches translations for all configured languages
4. Posts to Tolgee API with all translations
5. Optionally adds descriptions and tags

### Download Flow
1. Fetches all projects from Tolgee
2. For each language, retrieves translations in batches (1000 per page)
3. Filters by namespace prefix and sync state (REVIEWED)
4. Updates local PHP translation files
5. Preserves file structure and formatting

## License

Proprietary - Rackbeat Internal Use Only

## Support

For issues or questions, contact the Rackbeat development team.
