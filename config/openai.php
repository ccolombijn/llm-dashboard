<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key and Organization
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API. You can find your API key
    | on your OpenAI dashboard at https://platform.openai.com/api-keys.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION', null),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Base URL
    |--------------------------------------------------------------------------
    |
    | If you need to use a proxy or a specific base URL for the OpenAI API,
    | you can provide it here. Otherwise, leave empty to use the default.
    */
    'base_url' => env('OPENAI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will time out after 30 seconds.
    */

    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default model to use for requests to the
    | OpenAI API. This can be overridden on a per-request basis.
    */
    'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
];
