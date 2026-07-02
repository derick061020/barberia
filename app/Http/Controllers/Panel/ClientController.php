<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Jobs\PushApplePassUpdate;
use App\Models\Business;
use App\Models\Client;
use App\Services\GoogleWalletService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /** Negocio actual del MVP (en una versión multi-tenant vendría del usuario logueado). */
    private function business(): Business
    {
        return Business::firstOrCreate(
            ['slug' => 'demo'],
            ['name' => config('app.name', 'Barbería Demo'), 'description' => 'Tu barbería de confianza']
        );
    }

    public function index()
    {
        $clients = $this->business()->clients()->with('pass')->latest()->get();

        return view('panel.clients.index', [
            'business' => $this->business(),
            'clients'  => $clients,
        ]);
    }

    public function create()
    {
        return view('panel.clients.create', ['tiers' => Client::TIERS]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:120'],
            'phone'  => ['required', 'string', 'max:30'],
            'email'  => ['nullable', 'email', 'max:120'],
            'tier'   => ['required', Rule::in(Client::TIERS)],
            'points' => ['nullable', 'integer', 'min:0'],
        ]);

        $client = $this->business()->clients()->create($data);
        // Crea el pase (genera serial + token automáticamente)
        $client->pass()->firstOrCreate([]);

        return redirect()
            ->route('panel.clients.show', $client)
            ->with('ok', 'Cliente y pase creados.');
    }

    public function show(Client $client)
    {
        $client->pass()->firstOrCreate([]);

        return view('panel.clients.show', [
            'client' => $client->fresh(['pass', 'business']),
            'tiers'  => Client::TIERS,
        ]);
    }

    /** Actualiza datos del pase y dispara la notificación push (Google al instante). */
    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'tier'   => ['required', Rule::in(Client::TIERS)],
            'points' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:40'],
        ]);

        $client->update($data);

        $pass = $client->pass()->firstOrCreate([]);
        $pass->touchContent(); // Apple detectará el cambio vía el web service

        // Google: PATCH del objeto -> push automático al teléfono
        $google = GoogleWalletService::make();
        $googleMsg = '';
        if ($google->isConfigured() && $pass->google_object_id) {
            try {
                $google->pushUpdate($pass);
                $googleMsg = ' Google Wallet actualizado y notificado.';
            } catch (\Throwable $e) {
                $googleMsg = ' Aviso Google: ' . $e->getMessage();
            }
        }

        // Apple: encolar push APNs a los dispositivos registrados
        PushApplePassUpdate::dispatch($pass->id);

        return back()->with('ok', 'Pase actualizado.' . $googleMsg);
    }
}
