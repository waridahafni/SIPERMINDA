@extends('layouts.public')

@section('title', 'Beranda - BPS Kabupaten Padang Lawas')

@section('content')
    @php($sudahMasuk = session()->has('pemohon_id'))

    <div class="bg-gradient-to-br from-primary-500 to-primary-700 text-white">
        <div class="max-w-7xl mx-auto px-4 py-20 md:py-28 text-center">
            <h1 class="text-3xl md:text-5xl font-extrabold leading-tight">Selamat Datang di Portal Data<br>BPS Kabupaten Padang Lawas</h1>
            <p class="text-lg md:text-xl text-primary-200 mt-4 max-w-2xl mx-auto">Layanan permintaan data statistik yang cepat, mudah, dan transparan untuk masyarakat dan instansi.</p>
            <div class="flex flex-wrap justify-center gap-4 mt-8">
                <a href="{{ route('permintaan.create') }}" class="bg-white text-primary-600 font-semibold px-6 py-3 rounded-lg shadow hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary-600 transition">Ajukan Permintaan Data</a>
                <a href="{{ $sudahMasuk ? route('permintaan.create') : route('pemohon.daftar') }}" class="bg-secondary-500 text-white font-semibold px-6 py-3 rounded-lg shadow hover:bg-secondary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary-600 transition">
                    {{ $sudahMasuk ? 'Ajukan Permintaan' : '' }}
                </a>
            </div>
            @unless($sudahMasuk)
                <p class="mt-4 text-sm text-primary-100">
                    Sudah punya akun pemohon?
                    <a href="{{ route('pemohon.masuk') }}" class="font-semibold text-white underline underline-offset-4 hover:text-primary-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded">Masuk Pemohon</a>
                </p>
            @endunless
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 -mt-10">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 text-center flex flex-col hover:shadow-xl transition">
                <div class="w-14 h-14 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h3 class="font-bold text-lg text-gray-800">Alur Permintaan Data</h3>
                <p class="text-gray-500 text-sm mt-2 flex-1">Pahami langkah mudah mengajukan permintaan hingga data diterima.</p>
                <a href="{{ route('alur') }}" class="inline-block mt-4 text-primary-500 font-semibold text-sm hover:underline">Lihat Alur &rarr;</a>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 text-center flex flex-col hover:shadow-xl transition">
                <div class="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <h3 class="font-bold text-lg text-gray-800">Permintaan Data Mudah</h3>
                <p class="text-gray-500 text-sm mt-2 flex-1">Ajukan kebutuhan data Anda dengan proses yang cepat, aman, dan terstruktur.</p>
                <a href="{{ $sudahMasuk ? route('permintaan.create') : route('pemohon.daftar') }}" class="inline-block mt-4 text-primary-500 font-semibold text-sm hover:underline">Ajukan Sekarang &rarr;</a>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 text-center flex flex-col hover:shadow-xl transition">
                <div class="w-14 h-14 bg-secondary-100 text-secondary-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="font-bold text-lg text-gray-800">Ajukan Permintaan Data</h3>
                <p class="text-gray-500 text-sm mt-2 flex-1">Butuh data tertentu? Ajukan permintaan data statistik melalui akun pemohon.</p>
                <a href="{{ $sudahMasuk ? route('permintaan.create') : route('pemohon.daftar') }}" class="inline-block mt-4 text-primary-500 font-semibold text-sm hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded">
                    {{ $sudahMasuk ? 'Ajukan' : 'Daftar & Ajukan' }} &rarr;
                </a>
                @unless($sudahMasuk)
                    <a href="{{ route('pemohon.masuk') }}" class="inline-block mt-2 text-gray-500 font-medium text-xs hover:text-primary-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded">Sudah punya akun? Masuk</a>
                @endunless
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 text-center flex flex-col hover:shadow-xl transition">
                <div class="w-14 h-14 bg-yellow-100 text-Daftar & Ajukanyellow-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <h3 class="font-bold text-lg text-gray-800">Cek Status Permintaan</h3>
                <p class="text-gray-500 text-sm mt-2 flex-1">Pantau perkembangan permintaan data Anda secara real-time.</p>
                <a href="{{ route('cek-status') }}" class="inline-block mt-4 text-primary-500 font-semibold text-sm hover:underline">Cek Sekarang &rarr;</a>
            </div>
        </div>
    </div>

    <div class="bg-gray-100 mt-16 py-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h2 class="text-2xl font-bold text-gray-800">Tentang Layanan Ini</h2>
            <p class="text-gray-600 mt-4 max-w-3xl mx-auto">SIPERMINDA (Sistem Informasi Permintaan Data Statistik) adalah platform online yang memudahkan masyarakat dan instansi dalam mengajukan permintaan data statistik di BPS Kabupaten Padang Lawas. Dengan sistem ini, Anda dapat melacak status permintaan secara transparan.</p>
        </div>
    </div>
@endsection
