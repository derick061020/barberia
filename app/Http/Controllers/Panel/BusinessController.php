<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

/**
 * Configuración del negocio: datos y ubicación para las notificaciones
 * por proximidad (Apple Wallet + Google Wallet).
 */
class BusinessController extends Controller
{
    /** Negocio actual del MVP (igual que en ClientController). */
    private function business(): Business
    {
        return Business::firstOrCreate(
            ['slug' => 'demo'],
            ['name' => config('app.name', 'Barbería Demo'), 'description' => 'Tu barbería de confianza']
        );
    }

    public function edit()
    {
        return view('panel.business.edit', ['business' => $this->business()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:120'],
            'description'       => ['nullable', 'string', 'max:255'],
            'address'           => ['nullable', 'string', 'max:255'],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'proximity_radius'  => ['nullable', 'integer', 'min:10', 'max:1000'],
            'proximity_message' => ['nullable', 'string', 'max:255'],
        ]);

        // Lat y long van juntas: si falta una, se anulan ambas.
        if ($data['latitude'] === null || $data['longitude'] === null) {
            $data['latitude'] = null;
            $data['longitude'] = null;
        }

        $this->business()->update($data);

        return back()->with('ok', 'Datos del negocio guardados. Los pases nuevos ya usarán esta ubicación.');
    }
}
