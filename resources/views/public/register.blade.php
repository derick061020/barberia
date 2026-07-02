<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Únete a {{ $business->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-900 text-neutral-800 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="text-4xl mb-2">💈</div>
            <h1 class="text-2xl font-bold text-white">{{ $business->name }}</h1>
            <p class="text-neutral-400 text-sm mt-1">Crea tu tarjeta de fidelidad y añádela al Wallet.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}"
              class="bg-white rounded-2xl shadow-lg p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Nombre</label>
                <input name="name" value="{{ old('name') }}" required autofocus
                       class="w-full border border-neutral-300 rounded-lg px-3 py-2"
                       placeholder="Tu nombre">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Teléfono</label>
                <input name="phone" value="{{ old('phone') }}" required type="tel"
                       class="w-full border border-neutral-300 rounded-lg px-3 py-2"
                       placeholder="Ej: 600 123 456">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">
                    Email <span class="text-neutral-400 font-normal">(opcional)</span>
                </label>
                <input name="email" value="{{ old('email') }}" type="email"
                       class="w-full border border-neutral-300 rounded-lg px-3 py-2"
                       placeholder="tucorreo@ejemplo.com">
            </div>
            <button class="w-full bg-neutral-900 text-amber-400 font-semibold px-5 py-3 rounded-lg hover:bg-neutral-800">
                Crear mi tarjeta
            </button>
        </form>

        <p class="text-center text-neutral-500 text-xs mt-4">
            Al continuar aceptas recibir tu tarjeta digital de fidelidad.
        </p>
    </div>
</body>
</html>
