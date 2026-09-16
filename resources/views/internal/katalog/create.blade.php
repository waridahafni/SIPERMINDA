@extends('layouts.internal')

@section('title', 'Tambah Dataset')

@section('content')
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('internal.katalog.index') }}" class="text-sm text-primary-500 hover:underline">&larr; Kembali</a>
        <h1 class="text-2xl font-bold text-gray-800">Tambah Dataset Baru</h1>
    </div>

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('internal.katalog.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border p-8 space-y-6">
            @csrf

            @if($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Dataset</label>
                    <input type="text" name="judul" value="{{ old('judul') }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select name="kategori_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="">Pilih Kategori</option>
                        @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id') == $kat->id ? 'selected' : '' }}>{{ $kat->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                    <input type="text" name="periode" value="{{ old('periode') }}" required placeholder="Contoh: 2024"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="4" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">{{ old('deskripsi') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">File Dataset</label>
                    <input type="file" @unless(\App\Services\UnggahDokumen::langsung()) name="file" @endunless required accept=".pdf,.xls,.xlsx,.zip,.csv,.json"
                        class="w-full text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:bg-primary-50 file:text-primary-700 file:font-medium">
                    <p class="text-xs text-gray-400 mt-1">PDF, Excel, CSV, ZIP, atau JSON. Maksimal 50MB.</p>
                </div>
            </div>

            @include('internal.partials.unggah-langsung', ['tujuan' => 'katalog-baru'])
            <div class="flex gap-3">
                <button type="submit" class="bg-primary-500 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-primary-600 transition shadow">Simpan Dataset</button>
                <a href="{{ route('internal.katalog.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">Batal</a>
            </div>
        </form>
    </div>
@endsection
