<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'TradingAssistant') }} - @yield('title', 'Dashboard')</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#fff8f6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-background text-on-background min-h-screen pb-20 md:pb-0" style="font-family: 'Inter', sans-serif;">

    {{-- Top App Bar --}}
    <header class="w-full top-0 sticky bg-white border-b border-zinc-200 shadow-sm z-50">
        <div class="flex items-center justify-between px-4 h-16 w-full">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-orange-700 text-2xl">candlestick_chart</span>
                <h1 class="text-xl font-bold tracking-tight text-zinc-900">TradingAssistant</h1>
            </div>
            {{-- Desktop Nav --}}
            <nav class="hidden md:flex items-center gap-6">
                <a href="{{ route('dashboard') }}"
                   class="{{ request()->routeIs('dashboard') ? 'text-orange-600 font-medium' : 'text-zinc-500 hover:bg-zinc-100 transition-colors px-3 py-2 rounded-lg' }}">
                    Dashboard
                </a>
                <a href="{{ route('companies.index') }}"
                   class="{{ request()->routeIs('companies.*') ? 'text-orange-600 font-medium' : 'text-zinc-500 hover:bg-zinc-100 transition-colors px-3 py-2 rounded-lg' }}">
                    Companies
                </a>
                <a href="{{ route('orders.history') }}"
                   class="{{ request()->routeIs('orders.*') ? 'text-orange-600 font-medium' : 'text-zinc-500 hover:bg-zinc-100 transition-colors px-3 py-2 rounded-lg' }}">
                    Orders
                </a>
            </nav>
            <div class="flex items-center gap-2">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="p-2 rounded-full hover:bg-zinc-100 transition-colors">
                        <span class="material-symbols-outlined text-zinc-500">account_circle</span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition
                         class="absolute right-0 mt-2 w-44 bg-white rounded-xl shadow-lg border border-outline-variant py-1 z-50">
                        <div class="px-4 py-2 text-sm text-on-surface-variant border-b border-outline-variant">
                            {{ auth()->user()->name }}
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full text-left px-4 py-2 text-sm text-on-surface hover:bg-surface-container-low transition-colors">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border-b border-green-200 text-green-800 px-4 py-2 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-green-600 text-sm">check_circle</span>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border-b border-red-200 text-red-800 px-4 py-2 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-red-600 text-sm">error</span>
            {{ session('error') }}
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    {{-- Mobile Bottom Nav --}}
    <nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center h-16 px-6 bg-white/80 backdrop-blur-md border-t border-zinc-200 shadow-[0_-1px_3px_rgba(0,0,0,0.05)] text-[11px] font-medium">
        <a href="{{ route('dashboard') }}"
           class="flex flex-col items-center justify-center {{ request()->routeIs('dashboard') ? 'text-orange-600 bg-orange-50 rounded-xl px-3 py-1' : 'text-zinc-500' }} active:scale-90 transition-transform">
            <span class="material-symbols-outlined mb-0.5" @if(request()->routeIs('dashboard')) style="font-variation-settings: 'FILL' 1;" @endif>dashboard</span>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('companies.index') }}"
           class="flex flex-col items-center justify-center {{ request()->routeIs('companies.*') ? 'text-orange-600 bg-orange-50 rounded-xl px-3 py-1' : 'text-zinc-500' }} active:scale-90 transition-transform">
            <span class="material-symbols-outlined mb-0.5" @if(request()->routeIs('companies.*')) style="font-variation-settings: 'FILL' 1;" @endif>star</span>
            <span>Companies</span>
        </a>
        <a href="{{ route('orders.history') }}"
           class="flex flex-col items-center justify-center {{ request()->routeIs('orders.*') ? 'text-orange-600 bg-orange-50 rounded-xl px-3 py-1' : 'text-zinc-500' }} active:scale-90 transition-transform">
            <span class="material-symbols-outlined mb-0.5" @if(request()->routeIs('orders.*')) style="font-variation-settings: 'FILL' 1;" @endif>receipt_long</span>
            <span>Orders</span>
        </a>
    </nav>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').then(async (registration) => {
                // Only subscribe if permission not yet denied
                if (Notification.permission === 'denied') return;

                // Fetch the VAPID public key
                const res  = await fetch('/api/push/vapid-key');
                const data = await res.json();
                const applicationServerKey = urlBase64ToUint8Array(data.public_key);

                // Check for existing subscription
                let sub = await registration.pushManager.getSubscription();

                if (!sub) {
                    const perm = await Notification.requestPermission();
                    if (perm !== 'granted') return;

                    sub = await registration.pushManager.subscribe({
                        userVisibleOnly:      true,
                        applicationServerKey: applicationServerKey,
                    });
                }

                // Send subscription to server
                await fetch('/api/push/subscribe', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(sub.toJSON()),
                });

            }).catch(console.error);
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const raw     = atob(base64);
            return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
        }
    </script>
    @stack('scripts')
</body>
</html>
