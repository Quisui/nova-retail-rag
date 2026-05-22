<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'NovaRetail RAG')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
<div class="min-h-screen md:flex">
    <aside class="w-full md:w-72 bg-slate-900 text-slate-200 p-6">
        <div class="mb-8">
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">NovaRetail</p>
            <h1 class="text-2xl font-semibold">Knowledge RAG</h1>
        </div>

        <nav class="space-y-2">
            <a href="{{ route('dashboard', absolute: false) }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('documents.index', absolute: false) }}" class="sidebar-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">Documentos</a>
            <a href="{{ route('chat.index', absolute: false) }}" class="sidebar-link {{ request()->routeIs('chat.index') ? 'active' : '' }}">Chat IA</a>
            <a href="{{ route('chat.history', absolute: false) }}" class="sidebar-link {{ request()->routeIs('chat.history') ? 'active' : '' }}">Historial</a>
        </nav>
    </aside>

    <main class="flex-1 p-4 md:p-8">
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
