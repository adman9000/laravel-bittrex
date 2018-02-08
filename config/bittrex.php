<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Bittrex authentication
    |--------------------------------------------------------------------------
    |
    | Authentication key and secret for Bittrex API.
    |
     */

    'auth' => [
        'key'    => env('BITTREX_KEY', ''),
        'secret' => env('BITTREX_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Api URLS
    |--------------------------------------------------------------------------
    |
    | Bittrex API endpoints
    |
     */

    'urls' => [
        'public'  => 'https://bittrex.com/api/v1.1/public/',
        'publicv2'  => 'https://bittrex.com/Api/v2.0/pub/',
        'market'  => 'https://bittrex.com/api/v1.1/market/',
        'account' => 'https://bittrex.com/api/v1.1/account/',
    ],

];
