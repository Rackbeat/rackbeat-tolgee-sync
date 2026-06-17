<?php

return [
    /*
     * Tolgee API Configuration
     */
    'base_url' => env('TOLGEE_BASE_URL', 'https://translations.rkbt.io/v2/projects'),

    'base_project' => env('TOLGEE_BASE_PROJECT', 1),

    'sync_state' => env('TOLGEE_SYNC_STATE', 'REVIEWED'),

    'api_key' => env('TOLGEE_API_TOKEN'),

    /*
     * Namespace prefix for organizing keys in Tolgee
     */
    'namespace_prefix' => env('TOLGEE_NAMESPACE_PREFIX', 'b2b_'),

    /*
     * Master locale used as source of truth
     */
    'master_locale' => env('TOLGEE_MASTER_LOCALE', 'en'),

    /*
     * Master folder path
     */
    'master_folder' => 'resources/lang',

    /*
     * Languages to sync per project
     */
    'project_locales' => [
        1 => ['en', 'no', 'da', 'sv'],
    ],

    /*
     * Languages to skip when syncing (without namespace prefix)
     */
    'skip_namespaces' => [],
];

