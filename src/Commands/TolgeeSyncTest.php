<?php

namespace Rackbeat\TolgeeSync\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Rackbeat\TolgeeSync\Builders\TolgeeBuilder;

class TolgeeSyncTest extends Command
{
    protected $signature = 'tolgee:aaa';

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
    { dd($this->projectLocales);
    }
}

