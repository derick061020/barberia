<?php

namespace App\Jobs;

use App\Models\Pass;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Envía la notificación push de APNs a todos los iPhone que registraron el pase.
 *
 * El payload va vacío ("{}"): Apple solo necesita "despertar" al dispositivo, que
 * entonces llama a GET /wallet/v1/passes/... para descargar el pase actualizado.
 *
 * APNs se autentica con el MISMO certificado del Pass Type ID (TLS client cert).
 */
class PushApplePassUpdate implements ShouldQueue
{
    use Queueable;

    private const APNS_HOST = 'https://api.push.apple.com';

    public function __construct(public int $passId)
    {
    }

    public function handle(): void
    {
        $pass = Pass::with('devices')->find($this->passId);
        if (! $pass) {
            return;
        }

        $apple = config('passkit.apple');

        if (! is_file($apple['certificate_path']) || ! is_file($apple['private_key_path'])) {
            Log::warning('[APNs] Certificados no configurados; se omite el push.');
            return;
        }

        $topic = $apple['pass_type_identifier'];

        foreach ($pass->devices as $device) {
            $this->send($device->push_token, $topic, $apple);
        }
    }

    private function send(string $deviceToken, string $topic, array $apple): void
    {
        $ch = curl_init(self::APNS_HOST . '/3/device/' . $deviceToken);

        curl_setopt_array($ch, [
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '{}',
            CURLOPT_HTTPHEADER     => [
                'apns-topic: ' . $topic,
                'apns-push-type: background',
                'content-type: application/json',
            ],
            CURLOPT_SSLCERT        => $apple['certificate_path'],
            CURLOPT_SSLKEY         => $apple['private_key_path'],
            CURLOPT_SSLKEYPASSWD   => $apple['private_key_password'] ?: null,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            Log::error('[APNs] cURL error: ' . curl_error($ch));
        } elseif ($status !== 200) {
            Log::warning("[APNs] push a {$deviceToken} devolvió {$status}: {$response}");
        }

        curl_close($ch);
    }
}
