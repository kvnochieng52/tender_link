<?php

return [
    'consumer_key'    => env('MPESA_CONSUMER_KEY', ''),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET', ''),
    'short_code'      => env('MPESA_SHORT_CODE', '174379'),
    'passkey'         => env('MPESA_PASSKEY', ''),
    'callback_url'    => env('MPESA_CALLBACK_URL', ''),
    'sandbox'         => env('MPESA_SANDBOX', true),
];
