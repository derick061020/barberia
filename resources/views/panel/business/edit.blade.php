@extends('layouts.app')
@section('title', 'Configuración del negocio')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Configuración del negocio</h1>
    <p class="text-neutral-500 text-sm">Datos y ubicación para las notificaciones por proximidad.</p>
</div>

<form method="POST" action="{{ route('panel.business.update') }}" class="space-y-6 max-w-2xl">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="font-semibold text-lg">Datos generales</h2>

        <div>
            <label class="block text-sm font-medium mb-1">Nombre</label>
            <input type="text" name="name" value="{{ old('name', $business->name) }}" required
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Descripción</label>
            <input type="text" name="description" value="{{ old('description', $business->description) }}"
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2">
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <div>
            <h2 class="font-semibold text-lg">📍 Ubicación y proximidad</h2>
            <p class="text-neutral-500 text-sm">
                Cuando el cliente se acerque al local, el pase aparecerá en su pantalla de bloqueo
                (Apple Wallet y Google Wallet lo gestionan automáticamente). Déjalo vacío para desactivarlo.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Dirección (referencia)</label>
            <input type="text" name="address" value="{{ old('address', $business->address) }}"
                   placeholder="Av. Principal 123, Santo Domingo"
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Latitud</label>
                <input type="number" step="any" name="latitude" value="{{ old('latitude', $business->latitude) }}"
                       placeholder="18.486058"
                       class="w-full border border-neutral-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Longitud</label>
                <input type="number" step="any" name="longitude" value="{{ old('longitude', $business->longitude) }}"
                       placeholder="-69.931212"
                       class="w-full border border-neutral-300 rounded-lg px-3 py-2">
            </div>
        </div>
        <p class="text-xs text-neutral-400">
            Tip: en Google Maps, haz clic derecho sobre el local → copia las coordenadas (lat, long).
        </p>

        <div>
            <label class="block text-sm font-medium mb-1">Radio de la geocerca (metros)</label>
            <input type="number" name="proximity_radius" value="{{ old('proximity_radius', $business->proximity_radius) }}"
                   min="10" max="1000"
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2">
            <p class="text-xs text-neutral-400 mt-1">Solo Apple usa este radio. Google usa uno fijo (~150 m).</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Mensaje al estar cerca</label>
            <input type="text" name="proximity_message" value="{{ old('proximity_message', $business->proximity_message) }}"
                   placeholder="{{ '¡Estás cerca de ' . $business->name . '! Pasa a visitarnos 💈' }}"
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2">
            <p class="text-xs text-neutral-400 mt-1">Aparece en la pantalla de bloqueo (Apple). Vacío = mensaje por defecto.</p>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit"
                class="bg-amber-400 text-neutral-900 font-semibold px-5 py-2 rounded-lg hover:bg-amber-300">
            Guardar
        </button>
        <a href="{{ route('panel.clients.index') }}" class="text-neutral-500 hover:underline">Cancelar</a>
    </div>

    <p class="text-xs text-neutral-400">
        Nota: los pases ya descargados se actualizan al refrescarse vía el web service.
        Los cambios de ubicación aplican a los pases que se generen/actualicen a partir de ahora.
    </p>
</form>
@endsection
