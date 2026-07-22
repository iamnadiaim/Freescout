<?php

return [
    'oidc' => [
        'client_id' => env('OIDC_CLIENT_ID', '41'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect_uri' => env('OIDC_REDIRECT_URI'), // Dihapus default uc3-nya agar bisa otomatis mendeteksi URL asli aplikasi
        'url_authorize' => env('OIDC_URL_AUTHORIZE', 'https://sso.poliwangi.ac.id/oauth/authorize'),
        'url_access_token' => env('OIDC_URL_ACCESS_TOKEN', 'https://sso.poliwangi.ac.id/oauth/token'),
        'url_resource_owner_details' => env('OIDC_URL_RESOURCE_OWNER_DETAILS', 'https://sso.poliwangi.ac.id/api/user'),
        'scope' => env('OIDC_SCOPE', ''),
    ],
];
