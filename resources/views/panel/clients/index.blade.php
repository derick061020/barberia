@extends('layouts.app')
@section('title', 'Clientes')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Clientes</h1>
        <p class="text-neutral-500 text-sm">{{ $business->name }} — {{ $clients->count() }} registrados</p>
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
