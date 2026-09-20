@extends('layouts.public')

@section('title', 'Dashboard Akun - SIPERMINDA')

@section('content')
    <div class="bg-white border-b shadow-sm">
        <div class="max-w-5xl mx-auto px-4 py-8 sm:px-6">
            <p class="text-sm font-semibold uppercase tracking-wider text-primary-600">Akun Pemohon</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-800">Selamat datang, {{ $pemohon->nama }}</h1>
            <p class="mt-2 text-gray-500">Kelola profil dan pantau permintaan data Anda dalam satu tempat.</p>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 py-8 sm:px-6">
        <div class="grid gap-5 md:grid-cols-3">
            <a href="{{ route('permintaan.create') }}" class="rounded-xl border border-primary-200 bg-primary-50 p-5 transition hover:border-primary-400 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                <p class="font-semibold text-primary-800">Ajukan Permintaan</p><p class="mt-2 text-sm text-primary-700">Buat permintaan data baru.</p>
            </a>
            <a href="{{ route('pemohon.permintaan.index') }}" class="rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-300 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                <p class="font-semibold text-gray-800">Permintaan Saya</p><p class="mt-2 text-sm text-gray-500">Lihat status dan riwayat permintaan.</p>
            </a>
            <a href="{{ route('pemohon.profil') }}" class="rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-300 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                <p class="font-semibold text-gray-800">Profil Saya</p><p class="mt-2 text-sm text-gray-500">Perbarui informasi akun Anda.</p>
            </a>
        </div>

        <section class="mt-8" aria-labelledby="permintaan-terbaru">
            <div class="flex flex-wrap items-center justify-between gap-3"><h2 id="permintaan-terbaru" class="text-lg font-bold text-gray-800">Permintaan Terbaru</h2><a href="{{ route('pemohon.permintaan.index') }}" class="text-sm font-semibold text-primary-600 hover:underline">Lihat semua</a></div>
            @if($permintaanTerbaru->isEmpty())
                <div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500">Belum ada permintaan data. Mulai dengan membuat permintaan baru.</div>
            @else
                <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white divide-y">
                    @foreach($permintaanTerbaru as $item)
                        <a href="{{ route('pemohon.permintaan.show', $item) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500">
                            <div class="min-w-0"><p class="font-mono text-sm font-semibold text-primary-600">{{ $item->nomor_tiket }}</p><p class="mt-1 truncate text-sm text-gray-600">{{ $item->jenis_data }}</p></div>
                            <span class="shrink-0 text-xs font-semibold text-gray-500">{{ $item->created_at->format('d M Y') }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
