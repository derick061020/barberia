@extends('layouts.app')
@section('title', 'Nuevo cliente')

@section('content')
<h1 class="text-2xl font-bold mb-6">Nuevo cliente</h1>

<form method="POST" action="{{ route('panel.clients.store') }}"
      class="bg-white rounded-xl shadow-sm p-6 max-w-xl space-y-4">
    @csrf

    <div>
        <label class="block text-sm font-medium mb-1">Nombre *</label>
        <input name="name" value="{{ old('name') }}" required
               class="w-full border border-neutral-300 rounded-lg px-3 py-2">
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Teléfono *</label>
            <input name="phone" value="{{ old('phone') }}" required
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input name="email" type="email" value="{{ old('email') }}"
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Nivel</label>
            <select name="tier" class="w-full border border-neutral-300 rounded-lg px-3 py-2">
                @foreach ($tiers as $tier)
                    <option value="{{ $tier }}" @selected(old('tier') === $tier)>{{ ucfirst($tier) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Puntos iniciales</label>
            <input name="points" type="number" min="0" value="{{ old('points', 0) }}"
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2">
        </div>
    </div>

    <div class="pt-2 flex gap-3">
        <button class="bg-neutral-900 text-amber-400 font-semibold px-5 py-2 rounded-lg hover:bg-neutral-800">
            Crear cliente y pase
        </button>
        <a href="{{ route('panel.clients.index') }}"
           class="px-5 py-2 rounded-lg text-neutral-600 hover:bg-neutral-100">Cancelar</a>
    </div>
</form>
@endsection
