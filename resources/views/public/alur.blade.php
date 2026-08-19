@extends('layouts.public')

@section('title', 'Alur Permintaan Data - BPS Kabupaten Padang Lawas')

@section('content')
    @php($sudahMasuk = session()->has('pemohon_id'))

    <div class="bg-gradient-to-br from-primary-500 to-primary-700 text-white">
        <div class="max-w-7xl mx-auto px-4 py-16 md:py-24 text-center">
            <span class="inline-block bg-white/20 text-white text-xs font-semibold tracking-widest uppercase px-4 py-1.5 rounded-full mb-4">Panduan Layanan</span>
            <h1 class="text-3xl md:text-5xl font-extrabold leading-tight">Alur Permintaan Data</h1>
            <p class="text-lg md:text-xl text-primary-100 mt-4 max-w-2xl mx-auto">Ikuti langkah-langkah mudah berikut untuk mengajukan permintaan data statistik hingga data Anda diterima.</p>
            <div class="flex flex-wrap justify-center gap-4 mt-8">
                <a href="{{ $sudahMasuk ? route('permintaan.create') : route('pemohon.daftar') }}" class="bg-white text-primary-600 font-semibold px-6 py-3 rounded-lg shadow hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary-600 transition">
                    {{ $sudahMasuk ? 'Ajukan Sekarang' : 'Daftar & Ajukan' }}
                </a>
                <a href="{{ route('cek-status') }}" class="bg-transparent border-2 border-white/60 text-white font-semibold px-6 py-3 rounded-lg hover:bg-white/10 transition">Cek Status</a>
            </div>
            @unless($sudahMasuk)
                <p class="mt-4 text-sm text-primary-100">
                    Sudah terdaftar?
                    <a href="{{ route('pemohon.masuk') }}" class="font-semibold text-white underline underline-offset-4 hover:text-primary-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded">Masuk Pemohon</a>
                </p>
            @endunless
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 -mt-10">
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <div class="grid md:grid-cols-3 gap-6 text-center">
                <div>
                    <p class="text-4xl font-extrabold text-primary-500">100%</p>
                    <p class="text-gray-500 text-sm mt-1">Online &amp; Tanpa Antre</p>
                </div>
                <div>
                    <p class="text-4xl font-extrabold text-primary-500">3-5</p>
                    <p class="text-gray-500 text-sm mt-1">Hari Proses Permintaan</p>
                </div>
                <div>
                    <p class="text-4xl font-extrabold text-primary-500">24/7</p>
                    <p class="text-gray-500 text-sm mt-1">Pantau Status Kapan Saja</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 mt-16">
        <h2 class="text-2xl md:text-3xl font-bold text-center text-gray-800">Langkah Mudah, Semuanya Online</h2>
        <p class="text-gray-500 text-center mt-2 max-w-2xl mx-auto">Dari pengajuan hingga data diterima, seluruh proses dapat Anda pantau secara transparan melalui nomor tiket.</p>

        @php
            $steps = [
                [
                    'nomor' => '1',
                    'judul' => 'Daftar atau Masuk',
                    'deskripsi' => 'Daftar sebagai pemohon baru atau masuk dengan nomor WhatsApp yang sudah terdaftar. Kode OTP dikirim melalui WhatsApp untuk mengonfirmasi identitas.',
                    'warna' => 'bg-primary-500',
                    'lingkaran' => 'ring-primary-200',
                    'ikon' => 'M12 11c0 3.517-1.009 6.799-2.753 8.571m2.753-8.571a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zm6 0c0 3.517-1.009 6.799-2.753 8.571m2.753-8.571a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z',
                ],
                [
                    'nomor' => '2',
                    'judul' => 'Ajukan Permintaan Data',
                    'deskripsi' => 'Isi formulir kebutuhan data statistik Anda. Sistem otomatis menerbitkan nomor tiket untuk pelacakan.',
                    'warna' => 'bg-secondary-500',
                    'lingkaran' => 'ring-secondary-200',
                    'ikon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                ],
                [
                    'nomor' => '3',
                    'judul' => 'Permintaan Diverifikasi',
                    'deskripsi' => 'Petugas memeriksa kelengkapan dan kejelasan data yang Anda butuhkan.',
                    'warna' => 'bg-green-600',
                    'lingkaran' => 'ring-green-200',
                    'ikon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
                [
                    'nomor' => '4',
                    'judul' => 'Data Disiapkan',
                    'deskripsi' => 'Staf menyiapkan file data sesuai dengan permintaan Anda.',
                    'warna' => 'bg-blue-600',
                    'lingkaran' => 'ring-blue-200',
                    'ikon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                ],
                [
                    'nomor' => '5',
                    'judul' => 'Data Diterima &amp; Diunduh',
                    'deskripsi' => 'Saat data siap, masuk ke akun pemohon yang mengajukan permintaan untuk mengunduh file hasil dengan aman.',
                    'warna' => 'bg-yellow-500',
                    'lingkaran' => 'ring-yellow-200',
                    'ikon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
                ],
            ];
        @endphp

        <div class="max-w-3xl mx-auto mt-12">
            <ol class="relative space-y-10">
                @foreach($steps as $step)
                    <li class="relative flex gap-5">
                        <div class="flex flex-col items-center">
                            <span class="relative z-10 flex items-center justify-center w-14 h-14 rounded-full {{ $step['warna'] }} text-white shadow-lg ring-8 {{ $step['lingkaran'] }}">
                                <svg class="w-7 h-7" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $step['ikon'] }}"/>
                                </svg>
                            </span>
                            @if(!$loop->last)
                                <span class="w-0.5 flex-1 bg-gray-200 my-1"></span>
                            @endif
                        </div>
                        <div class="pb-2 flex-1">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full {{ $step['warna'] }} text-white text-xs font-bold mr-2 align-middle">{{ $step['nomor'] }}</span>
                            <h3 class="inline-block text-lg md:text-xl font-bold text-gray-800 align-middle">{{ $step['judul'] }}</h3>
                            <p class="text-gray-500 mt-1 leading-relaxed">{{ $step['deskripsi'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 mt-16">
        <div class="bg-gradient-to-br from-secondary-500 to-secondary-700 text-white rounded-3xl shadow-xl p-8 md:p-12 text-center relative overflow-hidden">
            <div class="absolute -top-8 -right-8 w-40 h-40 bg-white/10 rounded-full" aria-hidden="true"></div>
            <div class="absolute -bottom-10 -left-10 w-56 h-56 bg-white/10 rounded-full" aria-hidden="true"></div>
            <h2 class="text-2xl md:text-3xl font-bold relative">Siap Mengajukan Permintaan Data?</h2>
            <p class="text-secondary-100 mt-3 max-w-xl mx-auto relative">Prosesnya cepat dan seluruh status permintaan dapat Anda pantau secara transparan.</p>
            <div class="flex flex-wrap justify-center gap-4 mt-8 relative">
                <a href="{{ $sudahMasuk ? route('permintaan.create') : route('pemohon.daftar') }}" class="bg-white text-secondary-600 font-semibold px-8 py-3 rounded-lg shadow hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-secondary-600 transition">
                    {{ $sudahMasuk ? 'Ajukan Sekarang' : 'Daftar & Ajukan' }}
                </a>
                <a href="{{ route('katalog.index') }}" class="bg-transparent border-2 border-white/60 text-white font-semibold px-8 py-3 rounded-lg hover:bg-white/10 transition">Lihat Katalog</a>
            </div>
            @unless($sudahMasuk)
                <p class="mt-4 text-sm text-secondary-100 relative">
                    Sudah punya akun?
                    <a href="{{ route('pemohon.masuk') }}" class="font-semibold text-white underline underline-offset-4 hover:text-secondary-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded">Masuk Pemohon</a>
                </p>
            @endunless
        </div>
    </div>

    <div class="bg-gray-100 mt-16 py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8 text-center">
                <div>
                    <div class="w-12 h-12 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-800">Transparan</h3>
                    <p class="text-gray-500 text-sm mt-1">Setiap tahapan permintaan tercatat dan dapat dipantau.</p>
                </div>
                <div>
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-800">Cepat &amp; Efisien</h3>
                    <p class="text-gray-500 text-sm mt-1">Tidak perlu datang langsung, semua dilakukan secara online.</p>
                </div>
                <div>
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-800">Terpercaya</h3>
                    <p class="text-gray-500 text-sm mt-1">Data resmi dari BPS Kabupaten Padang Lawas.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
