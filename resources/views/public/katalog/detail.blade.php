@extends('layouts.public')

@section('title', $dataset->judul . ' - Katalog Data')

@section('content')
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <a href="{{ route('katalog.index') }}" class="text-sm text-primary-500 hover:underline">&larr; Kembali ke Katalog</a>
            <div class="mt-4">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-800">{{ $dataset->judul }}</h1>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full 
                        @if($dataset->status === 'aktif') bg-green-100 text-green-700
                        @else bg-gray-100 text-gray-700
                        @endif">{{ ucfirst($dataset->status) }}</span>
                    @if($dataset->versi)
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">v{{ $dataset->versi }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800">Deskripsi</h2>
                <p class="text-gray-600 mt-2 leading-relaxed">{{ $dataset->deskripsi ?? 'Tidak ada deskripsi.' }}</p>

                @if($dataset->file_path)
                    <div class="mt-8">
                        <a href="{{ route('katalog.unduh', $dataset) }}" class="inline-flex items-center gap-2 bg-primary-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-600 transition shadow">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Unduh Dataset
                        </a>
                    </div>
                @endif
            </div>

            <div class="bg-gray-50 rounded-xl p-6 space-y-4 text-sm">
                <div>
                    <p class="text-gray-500 font-medium">Kategori</p>
                    <p class="text-gray-800 font-semibold">{{ $dataset->kategori->nama ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-500 font-medium">Periode</p>
                    <p class="text-gray-800 font-semibold">{{ $dataset->periode }}</p>
                </div>
                <div>
                    <p class="text-gray-500 font-medium">Ukuran File</p>
                    <p class="text-gray-800 font-semibold">{{ $dataset->ukuran_file ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-500 font-medium">Tanggal Publish</p>
                    <p class="text-gray-800 font-semibold">{{ $dataset->created_at ? $dataset->created_at->format('d M Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-500 font-medium">Diupload Oleh</p>
                    <p class="text-gray-800 font-semibold">{{ $dataset->uploader ? $dataset->uploader->name : '-' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection