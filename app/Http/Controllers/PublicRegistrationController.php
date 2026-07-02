<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;

/**
 * Alta pública: el QR general (impreso en el local) apunta aquí.
 * El cliente rellena sus datos y se genera SU propia tarjeta al momento.
 */
class PublicRegistrationController extends Controller
{
    /** Negocio actual del MVP (igual que en el panel). */
    private function business(): Business
    {
        return Business::firstOrCreate(
            ['slug' => 'demo'],
            ['name' => config('app.name', 'Barbería Demo'), 'description' => 'Tu barbería de confianza']
        );
    }

    public function create()
    {
        return view('public.register', ['business' => $this->business()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
        ]);

        // tier, points y status usan sus valores por defecto en la BD.
        $client = $this->business()->clients()->create($data);
        // Crea el pase (genera serial + token automáticamente).
        $pass = $client->pass()->firstOrCreate([]);

        // Lleva directo a la página del pase para añadirlo al Wallet.
        return redirect()->route('pass.show', $pass);
    }
}
