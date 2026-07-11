<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the CORS middleware. Adjust these as needed to restrict
    | which origins can access your API.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
