<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Apple Wallet (PassKit)
    |--------------------------------------------------------------------------
    | Necesitas una cuenta Apple Developer ($99/año). Desde el portal generas:
    |   1. Un "Pass Type ID" (ej: pass.com.tunegocio.barberia)
    |   2. Un certificado para ese Pass Type ID -> lo exportas como .p12
    |   3. El certificado intermedio WWDR de Apple (AppleWWDRCAG4.cer)
    |
    | Convierte el .p12 a PEM (clave + certificado) con:
    |   openssl pkcs12 -in Certificates.p12 -clcerts -nokeys -out cert.pem
    |   openssl pkcs12 -in Certificates.p12 -nocerts -out key.pem   (te pide passphrase)
    |   openssl x509 -inform der -in AppleWWDRCAG4.cer -out wwdr.pem
    |
    | Coloca los 3 .pem en storage/app/passkit/certs/ (carpeta ignorada por git).
    */
    'apple' => [
        'pass_type_identifier' => env('APPLE_PASS_TYPE_ID'),
        'team_identifier'      => env('APPLE_TEAM_ID'),
        'organization_name'    => env('APPLE_ORG_NAME', env('APP_NAME', 'Barbería')),

        // Rutas a los certificados en PEM
        'certificate_path' => storage_path('app/passkit/certs/cert.pem'),
        'private_key_path' => storage_path('app/passkit/certs/key.pem'),
        'wwdr_path'        => storage_path('app/passkit/certs/wwdr.pem'),
        'private_key_password' => env('APPLE_CERT_PASSWORD', ''),

        // Imágenes por defecto del pase (icon.png es obligatorio para Apple)
        'assets_path' => storage_path('app/passkit/assets'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Wallet
    |--------------------------------------------------------------------------
    | Necesitas un proyecto en Google Cloud con la "Google Wallet API" activada
    | y una cuenta de servicio (Service Account) con su JSON de credenciales.
    | También un "Issuer ID" que se obtiene en el Google Pay & Wallet Console.
    |
    | Coloca el JSON en storage/app/passkit/google-service-account.json
    */
    'google' => [
        'issuer_id'            => env('GOOGLE_WALLET_ISSUER_ID'),
        'service_account_path' => storage_path('app/passkit/google-service-account.json'),
        // Sufijo de la clase de pase (loyalty class) que agrupa a todos los pases
        'class_suffix'         => env('GOOGLE_WALLET_CLASS_SUFFIX', 'barberia_loyalty'),
        'origins'              => array_filter(explode(',', (string) env('GOOGLE_WALLET_ORIGINS', ''))),
    ],

];
