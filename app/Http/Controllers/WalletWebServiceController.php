<?php

namespace App\Http\Controllers;

use App\Models\Pass;
use App\Services\ApplePassBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Web service oficial de Apple PassKit.
 *
 * Apple Wallet llama a estos endpoints automáticamente en el teléfono del cliente.
 * El webServiceURL del pase apunta aquí (/wallet) y cada llamada trae la cabecera
 * "Authorization: ApplePass <authenticationToken>".
 *
 * Docs: https://developer.apple.com/documentation/walletpasses
 */
class WalletWebServiceController extends Controller
{
    /** POST /v1/devices/{device}/registrations/{passType}/{serial} — registrar dispositivo. */
    public function register(Request $request, string $device, string $passType, string $serial)
    {
        $pass = $this->authorizePass($request, $serial);
        if (! $pass) {
            return response()->json([], 401);
        }

        $registration = $pass->devices()->firstOrCreate(
            ['device_library_identifier' => $device],
            ['push_token' => $request->input('pushToken')]
        );

        return response()->json([], $registration->wasRecentlyCreated ? 201 : 200);
    }

    /** DELETE /v1/devices/{device}/registrations/{passType}/{serial} — dar de baja. */
    public function unregister(Request $request, string $device, string $passType, string $serial)
    {
        $pass = $this->authorizePass($request, $serial);
        if (! $pass) {
            return response()->json([], 401);
        }

        $pass->devices()->where('device_library_identifier', $device)->delete();

        return response()->json([], 200);
    }

    /**
     * GET /v1/devices/{device}/registrations/{passType}?passesUpdatedSince=TAG
     * Devuelve los seriales de pases (de ese dispositivo) actualizados desde TAG.
     */
    public function updatedPasses(Request $request, string $device, string $passType)
    {
        $since = $request->query('passesUpdatedSince');

        $query = Pass::query()
            ->whereHas('devices', fn ($q) => $q->where('device_library_identifier', $device));

        if ($since) {
            $query->where('content_updated_at', '>', Carbon::createFromTimestamp((int) $since));
        }

        $passes = $query->get();

        if ($passes->isEmpty()) {
            return response()->json([], 204);
        }

        return response()->json([
            'lastUpdated'   => (string) $passes->max('content_updated_at')?->timestamp,
            'serialNumbers' => $passes->pluck('serial_number')->all(),
        ]);
    }

    /**
     * GET /v1/passes/{passType}/{serial} — entrega la versión más reciente del .pkpass.
     */
    public function latestPass(Request $request, string $passType, string $serial)
    {
        $pass = $this->authorizePass($request, $serial);
        if (! $pass) {
            return response()->json([], 401);
        }

        // 304 si el pase no cambió desde la última descarga del dispositivo
        $ifModifiedSince = $request->header('If-Modified-Since');
        if ($ifModifiedSince && $pass->content_updated_at
            && $pass->content_updated_at->lessThanOrEqualTo(Carbon::parse($ifModifiedSince))) {
            return response()->json([], 304);
        }

        $binary = ApplePassBuilder::make()->build($pass);

        return response($binary, 200, [
            'Content-Type'  => 'application/vnd.apple.pkpass',
            'Last-Modified' => $pass->content_updated_at?->toRfc7231String(),
        ]);
    }

    /** POST /v1/log — Apple envía aquí mensajes de diagnóstico. */
    public function log(Request $request)
    {
        Log::info('[PassKit] ' . json_encode($request->input('logs', [])));

        return response()->json([], 200);
    }

    /** Verifica la cabecera "Authorization: ApplePass <token>" contra el pase. */
    private function authorizePass(Request $request, string $serial): ?Pass
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'ApplePass ')) {
            return null;
        }

        $token = substr($header, strlen('ApplePass '));
        $pass  = Pass::where('serial_number', $serial)->first();

        if (! $pass || ! hash_equals($pass->authentication_token, $token)) {
            return null;
        }

        return $pass;
    }
}
