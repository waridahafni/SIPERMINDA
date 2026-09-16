<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIPERMINDA - Internal')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: { 50: '#e6f4fb', 100: '#c2e5f6', 200: '#8ccfec', 300: '#4fb5df', 400: '#159bd1', 500: '#057fbd', 600: '#05669a', 700: '#064b76', 800: '#063d61', 900: '#042d48' },
                        secondary: { 50: '#edf4fb', 100: '#d7e7f6', 200: '#b4d0eb', 300: '#83b0dd', 400: '#4b89c9', 500: '#0b5fa5', 600: '#084e89', 700: '#073e6d', 800: '#063255', 900: '#05243e' },
                        bpsGreen: { 500: '#6dbd2b', 600: '#579b20' },
                        bpsOrange: { 500: '#f58220', 600: '#d9680b' }
                    }
                }
            }
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>
    <noscript>
        <style>
            @media (min-width: 768px) {
                #navigasi-internal[x-cloak] { display: flex !important; }
            }
        </style>
    </noscript>
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-800"
    x-data="{
        sidebarOpen: window.matchMedia('(min-width: 768px)').matches,
        isDesktop: window.matchMedia('(min-width: 768px)').matches,
        init() {
            const mediaDesktop = window.matchMedia('(min-width: 768px)');
            mediaDesktop.addEventListener('change', (event) => {
                this.isDesktop = event.matches;
                this.sidebarOpen = event.matches;
            });
        },
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;

            if (!this.isDesktop && this.sidebarOpen) {
                this.$nextTick(() => this.$refs.sidebarFirstLink?.focus());
            }
        },
        closeMobileSidebar(returnFocus = false) {
            if (this.isDesktop || !this.sidebarOpen) {
                return;
            }

            this.sidebarOpen = false;

            if (returnFocus) {
                this.$nextTick(() => this.$refs.sidebarButton?.focus());
            }
        }
    }"
    @keydown.escape.window="closeMobileSidebar(true)">
    <div class="flex h-screen overflow-hidden">
        <aside id="navigasi-internal" x-show="sidebarOpen" x-cloak
            :class="isDesktop ? 'relative' : 'fixed inset-y-0 left-0 z-50 shadow-2xl'"
            class="w-64 bg-secondary-800 text-white flex-shrink-0 overflow-y-auto flex flex-col border-t-4 border-primary-400"
            aria-label="Navigasi utama petugas">
            <div class="p-4 border-b border-primary-400 flex items-center gap-3">
                <img src="{{ asset('images/logo-bps-padanglawas.svg') }}" alt="Logo Badan Pusat Statistik" class="h-10 w-auto">
                <div>
                    <p class="font-bold text-lg leading-tight">SIPERMINDA</p>
                    <p class="text-primary-200 text-xs">BPS Kab. Padang Lawas</p>
                </div>
            </div>
            <nav class="p-3 space-y-1 text-sm">
                @can('lihat-dashboard')
                    <a x-ref="sidebarFirstLink" href="{{ route('internal.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-primary-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>
                @endcan
                @can('lihat-permintaan')
                    <a href="{{ route('internal.permintaan.index') }}" class="flex items-center justify-between px-3 py-2 rounded hover:bg-primary-600 transition">
                        <span class="flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Permintaan Masuk
                        </span>
                        @if(isset($permintaanBaruCount) && $permintaanBaruCount > 0)
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $permintaanBaruCount }}</span>
                        @endif
                    </a>
                @endcan
                @if(Auth::user() && Auth::user()->hasRole('admin'))
                    <a href="{{ route('internal.pengguna.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-primary-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                        Manajemen User
                    </a>
                @endif
                @can('lihat-laporan')
                    <a href="{{ route('internal.laporan') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-primary-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Laporan
                    </a>
                @endcan
            </nav>
        </aside>

        <div x-show="sidebarOpen && !isDesktop" x-cloak
            x-transition.opacity
            @click="closeMobileSidebar(true)"
            class="fixed inset-0 z-40 bg-gray-900/60 md:hidden"
            aria-hidden="true"></div>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button type="button" x-ref="sidebarButton" @click="toggleSidebar()"
                        class="p-1 rounded hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                        :aria-label="sidebarOpen ? 'Tutup navigasi petugas' : 'Buka navigasi petugas'"
                        aria-controls="navigasi-internal" :aria-expanded="sidebarOpen.toString()">
                        <svg class="w-6 h-6 md:w-5 md:h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h2 class="text-lg font-semibold text-gray-700">@yield('title', 'Dashboard')</h2>
                </div>
                @auth
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600">{{ Auth::user()->name }}</span>
                        @php $roleNama = Auth::user()->getRoleNames()->first() ?? 'staf'; @endphp
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full 
                            @if($roleNama === 'admin') bg-red-100 text-red-700
                            @elseif($roleNama === 'kabid') bg-purple-100 text-purple-700
                            @elseif($roleNama === 'kasi') bg-indigo-100 text-indigo-700
                            @else bg-blue-100 text-blue-700
                            @endif">
                            {{ ucfirst($roleNama) }}
                        </span>
                        <form action="{{ route('internal.logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-red-600 transition flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Logout
                            </button>
                        </form>
                    </div>
                @endauth
            </header>

            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                @if(session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow mb-4">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow mb-4">{{ session('error') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
