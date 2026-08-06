@extends('layouts.public')

@section('title', 'Katalog Data - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-8">
            <h1 class="text-2xl font-bold text-gray-800">Katalog Data Statistik</h1>
            <p class="text-gray-500 mt-1">Jelajahi dan unduh dataset statistik yang tersedia.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <form method="GET" action="{{ route('katalog.index') }}" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari dataset..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>
            <div class="w-full md:w-48">
                <select name="kategori" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoris as $kat)
                        <option value="{{ $kat }}" {{ request('kategori') == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-primary-500 text-white px-6 py-2.5 rounded-lg hover:bg-primary-600 transition font-medium">Cari</button>
        </form>

        @if(request('search') || request('kategori'))
            <div class="mt-3 text-sm text-gray-500">
                Hasil filter:
                @if(request('search')) <span class="bg-gray-100 px-2 py-0.5 rounded">Cari: "{{ request('search') }}"</span> @endif
                @if(request('kategori')) <span class="bg-gray-100 px-2 py-0.5 rounded">Kategori: {{ request('kategori') }}</span> @endif
                <a href="{{ route('katalog.index') }}" class="text-primary-500 hover:underline ml-2">Reset</a>
            </div>
        @endif

        <div class="mt-6">
            @if($datasets->count() > 0)
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($datasets as $ds)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition p-5">
                            <div class="flex items-start justify-between">
                                <h3 class="font-semibold text-gray-800 text-lg leading-tight">{{ $ds->judul }}</h3>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-primary-100 text-primary-700 whitespace-nowrap ml-2">{{ $ds->kategori->nama ?? 'Umum' }}</span>
                            </div>
                            <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                                <span>{{ $ds->periode }}</span>
                                <span>{{ $ds->ukuran_file ?? '-' }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-3 line-clamp-2">{{ $ds->deskripsi ?? '' }}</p>
                            <div class="mt-4 flex gap-2">
                                <a href="{{ route('katalog.detail', $ds) }}" class="text-sm text-primary-500 hover:underline font-medium">Detail</a>
                                <a href="{{ route('katalog.unduh', $ds) }}" class="text-sm bg-primary-500 text-white px-3 py-1 rounded hover:bg-primary-600 transition font-medium">Unduh</a>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-8">
                    {{ $datasets->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="w-16 h-16 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <p class="text-gray-500 mt-4 text-lg">Belum ada data tersedia</p>
                    @if(request('search') || request('kategori'))
                        <p class="text-gray-400 mt-1">Coba ubah kata kunci atau filter kategori.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection