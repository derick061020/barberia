@extends('layouts.app')
@section('title', 'Clientes')

@php
    $registerUrl = route('register.create');
    $generalQr = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($registerUrl);
@endphp

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Clientes</h1>
        <p class="text-neutral-500 text-sm">{{ $business->name }} — {{ $clients->count() }} registrados</p>
    </div>
</div>

{{-- QR general: el mismo para todos. El cliente lo escanea y se da de alta solo. --}}
<div class="bg-white rounded-xl shadow-sm p-6 mb-6 flex flex-col sm:flex-row items-center gap-6">
    <img src="{{ $generalQr }}" alt="QR de alta" class="rounded-lg border border-neutral-200 w-40 h-40">
    <div class="text-center sm:text-left">
        <h2 class="font-semibold text-lg">QR de alta (para imprimir)</h2>
        <p class="text-sm text-neutral-500 mt-1 max-w-md">
            Imprímelo y colócalo en el local. Cualquier cliente lo escanea, rellena sus datos
            y obtiene su propia tarjeta en el Wallet. Es el mismo QR para todos.
        </p>
        <a href="{{ $registerUrl }}" target="_blank"
           class="inline-block mt-3 text-amber-600 font-semibold hover:underline break-all text-sm">
            {{ $registerUrl }} →
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500 uppercase text-xs">
            <tr>
                <th class="text-left px-4 py-3">Cliente</th>
                <th class="text-left px-4 py-3">Teléfono</th>
                <th class="text-left px-4 py-3">Nivel</th>
                <th class="text-left px-4 py-3">Puntos</th>
                <th class="text-right px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($clients as $client)
                <tr class="hover:bg-neutral-50">
                    <td class="px-4 py-3 font-medium">{{ $client->name }}</td>
                    <td class="px-4 py-3 text-neutral-600">{{ $client->phone }}</td>
                    <td class="px-4 py-3">
                        <span class="bg-neutral-900 text-amber-400 text-xs px-2 py-1 rounded-full">
                            {{ $client->tierLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $client->points }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('panel.clients.show', $client) }}"
                           class="text-amber-600 font-semibold hover:underline">Ver pase →</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-neutral-400">
                        Aún no hay clientes. Crea el primero con “+ Nuevo cliente”.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
