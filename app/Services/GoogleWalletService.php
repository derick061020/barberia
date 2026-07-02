<?php

namespace App\Services;

use App\Models\Pass;
use Firebase\JWT\JWT;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Integración con Google Wallet (pases de fidelidad / LoyaltyObject).
 *
 * Flujo:
 *   1. Crear UNA vez la "LoyaltyClass" (plantilla común a todos los pases del negocio).
 *   2. Crear/actualizar un "LoyaltyObject" por cliente vía REST API.
 *   3. Generar un enlace "Add to Google Wallet" (JWT firmado) para que el cliente lo guarde.
 *
 * Las actualizaciones remotas se hacen con PATCH al LoyaltyObject: Google dispara
 * la notificación push al teléfono automáticamente (equivalente al APNs de Apple).
 */
class GoogleWalletService
{
    private const BASE_URL = 'https://walletobjects.googleapis.com/walletobjects/v1';
    private const SCOPE    = 'https://www.googleapis.com/auth/wallet_object.issuer';
    private const SAVE_URL = 'https://pay.google.com/gp/v/save/';

    private ?array $credentials = null;

    public function __construct(private array $config)
    {
    }

    public static function make(): self
    {
        return new self(config('passkit.google'));
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['issuer_id']) && is_file($this->config['service_account_path']);
    }

    /**
     * Devuelve el enlace "Add to Google Wallet" para un pase, creando antes
     * la clase y el objeto si no existen.
     */
    public function saveLink(Pass $pass): string
    {
        $this->assertConfigured();
        $this->ensureClassExists();
        $this->upsertObject($pass);

        return self::SAVE_URL . $this->buildSaveJwt($pass);
    }

    /**
     * Actualiza el objeto de un cliente (puntos, nivel, estado…) y Google
     * envía la notificación push al wallet del cliente automáticamente.
     */
    public function pushUpdate(Pass $pass): void
    {
        $this->assertConfigured();
        $this->upsertObject($pass);
    }

    private function classId(): string
    {
        return $this->config['issuer_id'] . '.' . $this->config['class_suffix'];
    }

    private function objectId(Pass $pass): string
    {
        // Sufijo único por pase (sin guiones, Google solo permite [a-zA-Z0-9._-])
        return $this->config['issuer_id'] . '.' . str_replace('-', '', $pass->serial_number);
    }

    private function ensureClassExists(): void
    {
        $id    = $this->classId();
        $token = $this->accessToken();

        $exists = Http::withToken($token)->get(self::BASE_URL . "/loyaltyClass/{$id}");
        if ($exists->successful()) {
            return;
        }

        $business = $this->anyBusinessName();

        $response = Http::withToken($token)->post(self::BASE_URL . '/loyaltyClass', [
            'id'                => $id,
            'issuerName'        => $business,
            'programName'       => $business . ' - Fidelidad',
            'reviewStatus'      => 'UNDER_REVIEW',
            'hexBackgroundColor' => '#1a1a1a',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Error creando la LoyaltyClass: ' . $response->body());
        }
    }

    private function upsertObject(Pass $pass): void
    {
        $token  = $this->accessToken();
        $id     = $this->objectId($pass);
        $body   = $this->objectDefinition($pass);

        $existing = Http::withToken($token)->get(self::BASE_URL . "/loyaltyObject/{$id}");

        if ($existing->successful()) {
            // PATCH -> dispara la notificación de actualización en el teléfono
            $response = Http::withToken($token)
                ->patch(self::BASE_URL . "/loyaltyObject/{$id}", $body);
        } else {
            $response = Http::withToken($token)
                ->post(self::BASE_URL . '/loyaltyObject', $body);
        }

        if ($response->failed()) {
            throw new RuntimeException('Error guardando el LoyaltyObject: ' . $response->body());
        }

        if (empty($pass->google_object_id)) {
            $pass->forceFill(['google_object_id' => $id])->save();
        }
    }

    private function objectDefinition(Pass $pass): array
    {
        $client   = $pass->client;
        $business = $client->business;

        $object = [
            'id'                  => $this->objectId($pass),
            'classId'             => $this->classId(),
            'state'               => 'ACTIVE',
            'accountId'           => (string) $client->id,
            'accountName'         => $client->name,
            'loyaltyPoints'       => [
                'label'   => 'Puntos',
                'balance' => ['int' => (int) $client->points],
            ],
            'textModulesData' => [
                ['header' => 'Nivel',  'body' => $client->tierLabel(), 'id' => 'tier'],
                ['header' => 'Estado', 'body' => ucfirst($client->status), 'id' => 'status'],
            ],
            'barcode' => [
                'type'         => 'QR_CODE',
                'value'        => $pass->serial_number,
                'alternateText' => $client->name,
            ],
        ];

        // Notificación por proximidad: Google muestra el pase en el teléfono
        // cuando el cliente entra en la geocerca del local (radio fijo ~150 m).
        if ($business->hasLocation()) {
            $object['locations'] = [[
                'latitude'  => $business->latitude,
                'longitude' => $business->longitude,
            ]];
        }

        return $object;
    }

    /**
     * JWT firmado (RS256) con la clave privada de la cuenta de servicio.
     * Referencia el objeto ya creado; al abrir el enlace, Google lo añade al wallet.
     */
    private function buildSaveJwt(Pass $pass): string
    {
        $creds = $this->credentials();

        $payload = [
            'iss' => $creds['client_email'],
            'aud' => 'google',
            'typ' => 'savetowallet',
            'iat' => time(),
            'origins' => $this->config['origins'] ?: [rtrim(config('app.url'), '/')],
            'payload' => [
                'loyaltyObjects' => [
                    ['id' => $this->objectId($pass)],
                ],
            ],
        ];

        return JWT::encode($payload, $creds['private_key'], 'RS256');
    }

    private function accessToken(): string
    {
        $creds = new ServiceAccountCredentials(self::SCOPE, $this->credentials());
        $token = $creds->fetchAuthToken();

        if (empty($token['access_token'])) {
            throw new RuntimeException('No se pudo obtener el token de la cuenta de servicio de Google.');
        }

        return $token['access_token'];
    }

    private function credentials(): array
    {
        if ($this->credentials === null) {
            $json = file_get_contents($this->config['service_account_path']);
            $this->credentials = json_decode($json, true);

            if (empty($this->credentials['client_email']) || empty($this->credentials['private_key'])) {
                throw new RuntimeException('El JSON de la cuenta de servicio de Google es inválido.');
            }
        }

        return $this->credentials;
    }

    private function anyBusinessName(): string
    {
        return optional(\App\Models\Business::first())->name ?? config('app.name', 'Barbería');
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'Google Wallet no está configurado: define GOOGLE_WALLET_ISSUER_ID y coloca '
                . 'storage/app/passkit/google-service-account.json (ver config/passkit.php).'
            );
        }
    }
}
