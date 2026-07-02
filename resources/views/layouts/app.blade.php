<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Barbería Wallet')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-100 text-neutral-800 min-h-screen">
    <header class="bg-neutral-900 text-white">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ route('panel.clients.index') }}" class="font-bold text-lg tracking-wide">
                💈 Barbería <span class="text-amber-400">Wallet</span>
            </a>
            <nav class="flex items-center gap-3">
                <a href="{{ route('panel.business.edit') }}"
                   class="text-neutral-300 hover:text-white text-sm font-medium">
                    📍 Negocio
                </a>
                <a href="{{ route('panel.clients.create') }}"
                   class="bg-amber-400 text-neutral-900 font-semibold px-4 py-2 rounded-lg hover:bg-amber-300">
                    + Nuevo cliente
                </a>
            </nav>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8">
        @if (session('ok'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
