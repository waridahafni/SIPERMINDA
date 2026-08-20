<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BPS Kabupaten Padang Lawas')</title>
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
                        primary: { 50: '#e6eef7', 100: '#b3cceb', 200: '#80aadf', 300: '#4d88d3', 400: '#1a66c7', 500: '#003D7A', 600: '#003162', 700: '#00254a', 800: '#001832', 900: '#000c1a' },
                        secondary: { 50: '#eef2fb', 100: '#cad6f1', 200: '#a6bae7', 300: '#829edd', 400: '#5e82d3', 500: '#1E40AF', 600: '#1a3591', 700: '#152a73', 800: '#101f55', 900: '#0b1437' }
                    }
                }
            }
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>
    <noscript><style>[x-cloak] { display: block !important; } .js-only { display: none !important; }</style></noscript>
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-800" x-data="{ mobileMenu: false, loginMenu: false }">
    <a href="#konten-utama" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[60] focus:bg-white focus:text-primary-700 focus:px-4 focus:py-2 focus:rounded focus:shadow-lg">Lewati ke konten utama</a>
    <nav class="bg-primary-500 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="{{ route('beranda') }}" class="flex items-center gap-3 focus-visible:ring-2 focus-visible:ring-white rounded" aria-label="Beranda BPS Kabupaten Padang Lawas">
                    <img src="{{ asset('logo-bps.png') }}" alt="BPS" class="h-10 w-auto brightness-0 invert" onerror="this.style.display='none'">
                    <span class="font-bold text-lg hidden sm:block">BPS Kab. Padang Lawas</span>
                </a>
                <div class="hidden xl:flex items-center gap-4 text-sm font-medium">
                    <a href="{{ route('beranda') }}" class="hover:text-primary-200 transition">Beranda</a>
                    <a href="{{ route('alur') }}" class="hover:text-primary-200 transition">Alur Permintaan</a>
                    <a href="{{ route('katalog.index') }}" class="hover:text-primary-200 transition">Katalog Data</a>
                    <a href="{{ route('permintaan.create') }}" class="hover:text-primary-200 transition">Permintaan Data</a>
                    <a href="{{ route('cek-status') }}" class="hover:text-primary-200 transition">Cek Status</a>
                    @if($pemohonAktif)
                        <a href="{{ route('pemohon.permintaan.index') }}" class="hover:text-primary-200 transition">Permintaan Saya</a>
                        <span class="text-primary-200" aria-hidden="true">|</span>
                        <span class="text-white font-semibold max-w-32 truncate" title="{{ $pemohonAktif->nama }}">{{ $pemohonAktif->nama }}</span>
                        <form method="POST" action="{{ route('pemohon.keluar') }}">
                            @csrf
                            <button type="submit" class="border border-white/60 hover:bg-primary-600 px-3 py-1.5 rounded transition focus-visible:ring-2 focus-visible:ring-white">Keluar</button>
                        </form>
                        <span class="h-6 border-l border-primary-300" aria-hidden="true"></span>
                        <a href="{{ route('internal.login') }}" class="text-primary-100 hover:text-white transition" title="Khusus pegawai BPS">Masuk Petugas</a>
                    @else
                        <details x-ref="loginDetails" class="relative"
                            @toggle="loginMenu = $el.open; if ($el.open) { mobileMenu = false; $nextTick(() => $refs.loginPemohon.focus()) }"
                            @click.outside="if ($el.open) { $el.open = false }"
                            @keydown.escape.stop.prevent="if ($el.open) { $el.open = false; $nextTick(() => $refs.loginSummary.focus()) }"
                            @focusout="if ($el.open && !$el.contains($event.relatedTarget)) { $el.open = false }">
                            <summary id="tombol-masuk-desktop" x-ref="loginSummary"
                                class="inline-flex cursor-pointer list-none items-center gap-1.5 border border-white/60 px-3 py-1.5 rounded hover:bg-primary-600 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white [&::-webkit-details-marker]:hidden"
                                aria-controls="menu-masuk-desktop" :aria-expanded="loginMenu.toString()">
                                Masuk
                                <svg class="w-4 h-4 transition-transform" :class="loginMenu ? 'rotate-180' : ''" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </summary>
                            <div id="menu-masuk-desktop"
                                class="absolute right-0 top-full mt-2 w-72 overflow-hidden rounded-xl bg-white text-gray-800 shadow-xl ring-1 ring-black/10 z-[60]"
                                role="group" aria-labelledby="tombol-masuk-desktop">
                                <a x-ref="loginPemohon" href="{{ route('pemohon.masuk') }}"
                                    class="block px-4 py-3 hover:bg-primary-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500">
                                    <span class="block font-semibold text-primary-700">Masuk sebagai Pemohon</span>
                                    <span class="block mt-0.5 text-xs font-normal text-gray-600">Untuk masyarakat atau instansi, menggunakan OTP WhatsApp.</span>
                                </a>
                                <a href="{{ route('internal.login') }}"
                                    class="block border-t border-gray-100 px-4 py-3 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500">
                                    <span class="block font-semibold text-gray-800">Masuk sebagai Petugas</span>
                                    <span class="block mt-0.5 text-xs font-normal text-gray-600">Khusus pegawai BPS yang memiliki akun internal.</span>
                                </a>
                            </div>
                        </details>
                        <a href="{{ route('pemohon.daftar') }}" class="bg-white text-primary-600 font-semibold px-3 py-1.5 rounded hover:bg-primary-100 transition">Daftar Pemohon</a>
                    @endif
                </div>
                <button type="button" x-ref="menuButton" @click="if ($refs.loginDetails) { $refs.loginDetails.open = false }; loginMenu = false; mobileMenu = !mobileMenu"
                    @keydown.escape.window="if (mobileMenu) { mobileMenu = false; $nextTick(() => $refs.menuButton.focus()) }"
                    class="xl:hidden p-2 rounded hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-white"
                    :aria-label="mobileMenu ? 'Tutup menu navigasi' : 'Buka menu navigasi'" aria-controls="menu-mobile" :aria-expanded="mobileMenu.toString()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
        <div id="menu-mobile" x-show="mobileMenu" x-cloak @click.outside="mobileMenu = false" class="xl:hidden bg-primary-600 px-4 py-3 space-y-1 text-sm">
            <a href="{{ route('beranda') }}" class="block py-3 hover:text-primary-200">Beranda</a>
            <a href="{{ route('alur') }}" class="block py-3 hover:text-primary-200">Alur Permintaan</a>
            <a href="{{ route('katalog.index') }}" class="block py-3 hover:text-primary-200">Katalog Data</a>
            <a href="{{ route('permintaan.create') }}" class="block py-3 hover:text-primary-200">Permintaan Data</a>
            <a href="{{ route('cek-status') }}" class="block py-3 hover:text-primary-200">Cek Status</a>
            @if($pemohonAktif)
                <hr class="border-primary-400">
                <span class="block py-3 font-semibold">{{ $pemohonAktif->nama }}</span>
                <a href="{{ route('pemohon.permintaan.index') }}" class="block py-3 hover:text-primary-200">Permintaan Saya</a>
                <form method="POST" action="{{ route('pemohon.keluar') }}">
                    @csrf
                    <button type="submit" class="w-full text-left py-3 font-semibold hover:text-primary-200">Keluar</button>
                </form>
                <hr class="border-primary-400">
                <p class="text-xs text-primary-200 pt-3">Khusus pegawai BPS</p>
                <a href="{{ route('internal.login') }}" class="block py-3 font-semibold hover:text-primary-200">Masuk Petugas</a>
            @else
                <div class="border-t border-primary-400 pt-3 mt-1" aria-labelledby="menu-masuk-mobile-title">
                    <p id="menu-masuk-mobile-title" class="font-semibold">Masuk</p>
                    <div class="grid sm:grid-cols-2 gap-2 mt-2">
                        <a href="{{ route('pemohon.masuk') }}" class="block rounded-lg border border-white/50 px-3 py-2.5 hover:bg-primary-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            <span class="block font-semibold">Sebagai Pemohon</span>
                            <span class="block mt-0.5 text-xs font-normal text-primary-100">Masyarakat/instansi via OTP WhatsApp</span>
                        </a>
                        <a href="{{ route('internal.login') }}" class="block rounded-lg border border-white/50 px-3 py-2.5 hover:bg-primary-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            <span class="block font-semibold">Sebagai Petugas</span>
                            <span class="block mt-0.5 text-xs font-normal text-primary-100">Khusus pegawai BPS</span>
                        </a>
                    </div>
                    <a href="{{ route('pemohon.daftar') }}" class="block mt-3 text-center bg-white text-primary-600 px-3 py-3 rounded font-semibold hover:bg-primary-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary-600">Daftar Pemohon</a>
                </div>
            @endif
        </div>
    </nav>

    <noscript>
        <div role="status" class="bg-yellow-50 border-b border-yellow-200 px-4 py-3 text-center text-sm text-yellow-900">
            JavaScript tidak aktif. Menu dan isian tetap ditampilkan, tetapi hitung mundur OTP tidak diperbarui otomatis.
        </div>
    </noscript>

    <main id="konten-utama" tabindex="-1" class="min-h-screen">
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 mt-4">
                <div role="status" aria-live="polite" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow">{{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 mt-4">
                <div role="alert" aria-live="assertive" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow">{{ session('error') }}</div>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="bg-primary-500 text-white mt-12">
        <div class="max-w-7xl mx-auto px-4 py-8 text-center text-sm">
            <p class="font-semibold">BPS Kabupaten Padang Lawas</p>
            <p class="text-primary-200 mt-1">Jl. Padang Lawas No. 1, Sibuhuan, Kec. Barumun, Kab. Padang Lawas, Sumatera Utara</p>
            <p class="text-primary-200 mt-1">&copy; {{ date('Y') }} Badan Pusat Statistik. All rights reserved.</p>
        </div>
    </footer>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
