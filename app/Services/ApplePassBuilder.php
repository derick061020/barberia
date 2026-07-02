<?php

namespace App\Services;

use App\Models\Pass;
use RuntimeException;
use ZipArchive;

/**
 * Genera un archivo .pkpass firmado para Apple Wallet.
 *
 * Un .pkpass es un ZIP que contiene:
 *   - pass.json     (el contenido del pase)
 *   - manifest.json (SHA1 de cada archivo del paquete)
 *   - signature     (firma PKCS#7 detached del manifest, hecha con tu certificado)
 *   - imágenes       (icon.png obligatorio, logo, etc.)
 *
 * Apple valida la firma contra el certificado del Pass Type ID + el WWDR de Apple.
 */
class ApplePassBuilder
{
    public function __construct(private array $config)
    {
    }

    public static function make(): self
    {
        return new self(config('passkit.apple'));
    }

    /**
     * Construye el .pkpass y devuelve su contenido binario (para descargar).
     */
    public function build(Pass $pass): string
    {
        $this->assertConfigured();

        $passJson = json_encode(
            $this->passDefinition($pass),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        // 1. Reunir todos los archivos del paquete (nombre => contenido binario)
        $files = ['pass.json' => $passJson];
        foreach ($this->assetFiles($pass) as $name => $path) {
            $files[$name] = file_get_contents($path);
        }

        // 2. manifest.json: SHA1 de cada archivo
        $manifest = [];
        foreach ($files as $name => $contents) {
            $manifest[$name] = sha1($contents);
        }
        $manifestJson = json_encode($manifest, JSON_UNESCAPED_SLASHES);
        $files['manifest.json'] = $manifestJson;

        // 3. Firmar el manifest -> signature
        $files['signature'] = $this->sign($manifestJson);

        // 4. Empaquetar en ZIP (.pkpass)
        return $this->zip($files);
    }

    /**
     * Estructura del pase. Tipo "storeCard" = tarjeta de fidelidad.
     */
    private function passDefinition(Pass $pass): array
    {
        $client   = $pass->client;
        $business = $client->business;

        $definition = [
            'formatVersion'        => 1,
            'passTypeIdentifier'   => $this->config['pass_type_identifier'],
            'teamIdentifier'       => $this->config['team_identifier'],
            'organizationName'     => $business->name,
            'serialNumber'         => $pass->serial_number,
            'description'          => $business->name . ' - Tarjeta de cliente',

            'foregroundColor'      => $business->foreground_color,
            'backgroundColor'      => $business->background_color,
            'labelColor'           => $business->label_color,
            'logoText'             => $business->name,

            // Web service: permite las actualizaciones remotas + push
            'webServiceURL'        => rtrim(config('app.url'), '/') . '/wallet',
            'authenticationToken'  => $pass->authentication_token,

            // Código QR con el serial (lo escanea el negocio para identificar al cliente)
            'barcodes' => [[
                'format'          => 'PKBarcodeFormatQR',
                'message'         => $pass->serial_number,
                'messageEncoding' => 'iso-8859-1',
                'altText'         => $client->name,
            ]],

            'storeCard' => [
                'primaryFields' => [[
                    'key'   => 'points',
                    'label' => 'PUNTOS',
                    'value' => $client->points,
                ]],
                'secondaryFields' => [
                    [
                        'key'   => 'name',
                        'label' => 'CLIENTE',
                        'value' => $client->name,
                    ],
                    [
                        'key'   => 'tier',
                        'label' => 'NIVEL',
                        'value' => $client->tierLabel(),
                    ],
                ],
                'auxiliaryFields' => [[
                    'key'   => 'status',
                    'label' => 'ESTADO',
                    'value' => ucfirst($client->status),
                ]],
                'backFields' => [
                    [
                        'key'   => 'about',
                        'label' => 'Negocio',
                        'value' => $business->description ?: $business->name,
                    ],
                    [
                        'key'   => 'phone',
                        'label' => 'Teléfono',
                        'value' => $client->phone,
                    ],
                ],
            ],
        ];

        // Notificación por proximidad: si el negocio tiene coordenadas, Apple Wallet
        // mostrará el pase en la pantalla de bloqueo cuando el cliente esté cerca.
        if ($business->hasLocation()) {
            $definition['locations'] = [[
                'latitude'     => $business->latitude,
                'longitude'    => $business->longitude,
                'relevantText' => $business->proximityText(),
            ]];
            $definition['maxDistance'] = $business->proximity_radius ?: 100;
        }

        return $definition;
    }

    /**
     * Imágenes a incluir. Usa el logo del negocio si existe; si no, los assets por defecto.
     */
    private function assetFiles(Pass $pass): array
    {
        $base   = rtrim($this->config['assets_path'], '/');
        $assets = [];

        foreach (['icon.png', 'icon@2x.png', 'logo.png', 'logo@2x.png'] as $name) {
            $path = $base . '/' . $name;
            if (is_file($path)) {
                $assets[$name] = $path;
            }
        }

        if (! isset($assets['icon.png'])) {
            throw new RuntimeException("Falta icon.png en {$base} (Apple lo exige).");
        }

        return $assets;
    }

    /**
     * Firma PKCS#7 detached del manifest usando el certificado del Pass Type ID.
     * Devuelve la firma en formato DER (lo que espera Apple en el archivo 'signature').
     */
    private function sign(string $manifestJson): string
    {
        $tmpManifest  = tempnam(sys_get_temp_dir(), 'pkmanifest');
        $tmpSignature = tempnam(sys_get_temp_dir(), 'pksignature');
        file_put_contents($tmpManifest, $manifestJson);

        $ok = openssl_pkcs7_sign(
            $tmpManifest,
            $tmpSignature,
            'file://' . $this->config['certificate_path'],
            ['file://' . $this->config['private_key_path'], $this->config['private_key_password']],
            [],
            PKCS7_BINARY | PKCS7_DETACHED,
            $this->config['wwdr_path']
        );

        if (! $ok) {
            @unlink($tmpManifest);
            @unlink($tmpSignature);
            throw new RuntimeException(
                'No se pudo firmar el pase. Revisa los certificados PEM y la contraseña. '
                . openssl_error_string()
            );
        }

        // La salida es S/MIME (PEM). Extraemos el bloque base64 y lo pasamos a DER.
        $smime = file_get_contents($tmpSignature);
        @unlink($tmpManifest);
        @unlink($tmpSignature);

        $der = $this->smimeToDer($smime);
        if ($der === null) {
            throw new RuntimeException('No se pudo convertir la firma S/MIME a DER.');
        }

        return $der;
    }

    /**
     * Extrae el contenido DER (binario) de la firma S/MIME generada por openssl.
     *
     * openssl produce un mensaje "multipart/signed": una parte con el contenido
     * original y otra con la firma PKCS#7 en base64. Tomamos esa segunda parte.
     */
    private function smimeToDer(string $smime): ?string
    {
        $smime = str_replace("\r\n", "\n", $smime);

        if (! preg_match('/boundary="?([^";\n]+)"?/', $smime, $bm)) {
            return null;
        }
        $boundary = '--' . $bm[1];

        foreach (explode($boundary, $smime) as $part) {
            // La parte de la firma contiene la cabecera pkcs7-signature Y va en base64.
            // (El preámbulo también menciona "pkcs7-signature" en el protocolo, hay que excluirlo.)
            if (stripos($part, 'pkcs7-signature') === false
                || stripos($part, 'base64') === false) {
                continue;
            }
            // Separar cabeceras del cuerpo (primera línea en blanco)
            $segments = preg_split('/\n\s*\n/', $part, 2);
            if (count($segments) < 2) {
                continue;
            }
            $base64 = preg_replace('/[^A-Za-z0-9+\/=]/', '', $segments[1]);
            $der    = base64_decode($base64, true);

            return $der !== false && $der !== '' ? $der : null;
        }

        return null;
    }

    private function zip(array $files): string
    {
        $tmpZip = tempnam(sys_get_temp_dir(), 'pkpass');
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el ZIP del pase.');
        }

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        $binary = file_get_contents($tmpZip);
        @unlink($tmpZip);

        return $binary;
    }

    private function assertConfigured(): void
    {
        if (empty($this->config['pass_type_identifier']) || empty($this->config['team_identifier'])) {
            throw new RuntimeException(
                'Apple Wallet no está configurado: define APPLE_PASS_TYPE_ID y APPLE_TEAM_ID en .env.'
            );
        }

        foreach (['certificate_path', 'private_key_path', 'wwdr_path'] as $key) {
            if (! is_file($this->config[$key])) {
                throw new RuntimeException(
                    "Falta el certificado: {$this->config[$key]}. "
                    . 'Coloca cert.pem, key.pem y wwdr.pem en storage/app/passkit/certs/ (ver config/passkit.php).'
                );
            }
        }
    }

    public function isConfigured(): bool
    {
        try {
            $this->assertConfigured();
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }
}
