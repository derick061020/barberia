<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tu tarjeta — {{ $pass->client->business->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        {{-- Tarjeta visual --}}
        <div class="rounded-2xl p-6 text-white shadow-2xl"
             style="background: {{ $pass->client->business->background_color }};">
            <div class="flex items-center justify-between mb-8">
                <span class="font-bold tracking-wide">{{ $pass->client->business->name }}</span>
                <span class="text-xs bg-white/15 px-2 py-1 rounded-full">{{ $pass->client->tierLabel() }}</span>
            </div>
            <div class="text-xs uppercase opacity-60">Cliente</div>
            <div class="text-xl font-semibold mb-4">{{ $pass->client->name }}</div>
            <div class="flex justify-between items-end">
                <div>
                    <div class="text-xs uppercase opacity-60">Puntos</div>
                    <div class="text-3xl font-bold text-amber-400">{{ $pass->client->points }}</div>
                </div>
                <div class="text-right">
                    <div class="text-xs uppercase opacity-60">Estado</div>
                    <div class="font-medium">{{ ucfirst($pass->client->status) }}</div>
                </div>
            </div>
        </div>

        {{-- Botones de wallet --}}
        <div class="mt-6 space-y-3">
            @if ($appleReady)
                <a href="{{ route('pass.apple', $pass) }}"
                   class="flex items-center justify-center gap-2 bg-black text-white font-semibold py-3 rounded-xl">
                     Añadir a Apple Wallet
                </a>
            @else
                <div class="text-center text-neutral-400 text-sm bg-neutral-800 py-3 rounded-xl">
                    Apple Wallet pendiente de configurar certificados
                </div>
            @endif

            @if ($googleReady)
                <a href="{{ route('pass.google', $pass) }}"
                   class="flex items-center justify-center gap-2 bg-white text-neutral-900 font-semibold py-3 rounded-xl">
                    Añadir a Google Wallet
                </a>
            @else
                <div class="text-center text-neutral-400 text-sm bg-neutral-800 py-3 rounded-xl">
                    Google Wallet pendiente de configurar credenciales
                </div>
            @endif
        </div>

        <p class="text-center text-neutral-500 text-xs mt-6">
            Una vez añadido, tu tarjeta se actualiza sola con promociones y puntos.
        </p>
    </div>
</body>
</html>
