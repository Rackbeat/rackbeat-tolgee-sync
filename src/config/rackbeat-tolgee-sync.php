<?php

return [
    /*
     * Tolgee API Configuration
     */
    'base_url' => env('TOLGEE_BASE_URL'),

    'base_project' => env('TOLGEE_BASE_PROJECT', 1),

    'sync_state' => env('TOLGEE_SYNC_STATE', 'REVIEWED'),

    'api_key' => env('TOLGEE_API_TOKEN'),

    'namespace_prefix' => env('TOLGEE_NAMESPACE_PREFIX'),

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
        1 => json_decode(env('PROJECT_LOCALES', '[]'), true) ?? [],
    ],

    /*
     * Languages to skip when syncing (without namespace prefix)
     */
    'skip_namespaces' => [],
];

