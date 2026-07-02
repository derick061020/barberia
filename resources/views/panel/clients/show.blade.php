@extends('layouts.app')
@section('title', $client->name)

@php
    $passUrl = route('pass.show', $client->pass);
    $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($passUrl);
@endphp

@section('content')
<a href="{{ route('panel.clients.index') }}" class="text-sm text-neutral-500 hover:underline">← Volver a clientes</a>

<div class="grid md:grid-cols-3 gap-6 mt-4">
    {{-- Datos + actualización --}}
    <div class="md:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h1 class="text-2xl font-bold">{{ $client->name }}</h1>
            <p class="text-neutral-500">{{ $client->phone }} @if($client->email) · {{ $client->email }} @endif</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold mb-1">Actualizar pase</h2>
            <p class="text-sm text-neutral-500 mb-4">
                Al guardar, el wallet del cliente se actualiza y recibe una notificación push automática.
            </p>
            <form method="POST" action="{{ route('panel.clients.update', $client) }}"
                  class="grid grid-cols-3 gap-4 items-end">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium mb-1">Nivel</label>
                    <select name="tier" class="w-full border border-neutral-300 rounded-lg px-3 py-2">
                        @foreach ($tiers as $tier)
                            <option value="{{ $tier }}" @selected($client->tier === $tier)>{{ ucfirst($tier) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Puntos</label>
                    <input name="points" type="number" min="0" value="{{ $client->points }}"
                           class="w-full border border-neutral-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Estado</label>
                    <input name="status" value="{{ $client->status }}"
                           class="w-full border border-neutral-300 rounded-lg px-3 py-2">
                </div>
                <div class="col-span-3">
                    <button class="bg-neutral-900 text-amber-400 font-semibold px-5 py-2 rounded-lg hover:bg-neutral-800">
                        Guardar y notificar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- QR / enlace al pase --}}
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <h2 class="font-semibold mb-3">Pase del cliente</h2>
        <img src="{{ $qr }}" alt="QR" class="mx-auto rounded-lg border border-neutral-200">
        <p class="text-xs text-neutral-500 mt-3">
            El cliente escanea este QR para abrir su pase y añadirlo al wallet.
        </p>
        <a href="{{ $passUrl }}" target="_blank"
           class="inline-block mt-4 text-amber-600 font-semibold hover:underline break-all text-sm">
            Abrir página del pase →
        </a>
        <div class="mt-4 text-left text-xs text-neutral-400 border-t pt-3">
            <div>Serial: {{ $client->pass->serial_number }}</div>
        </div>
    </div>
</div>
@endsection
