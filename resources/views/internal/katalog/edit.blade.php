@extends('layouts.internal')

@section('title', (request('revisi') ? 'Revisi ' : 'Edit ') . 'Dataset')

@section('content')
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('internal.katalog.index') }}" class="text-sm text-primary-500 hover:underline">&larr; Kembali</a>
        <h1 class="text-2xl font-bold text-gray-800">{{ request('revisi') ? 'Revisi Dataset' : 'Edit Dataset' }}</h1>
    </div>

    <div class="max-w-3xl">
        <form method="POST" action="{{ request('revisi') ? route('internal.katalog.revisi', $dataset) : route('internal.katalog.update', $dataset) }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border p-8 space-y-6">
            @csrf
            @if(!request('revisi'))
                @method('PUT')
            @endif

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
                    <input type="text" name="judul" value="{{ old('judul', $dataset->judul) }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select name="kategori_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="">Pilih Kategori</option>
                        @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id', $dataset->kategori_id) == $kat->id ? 'selected' : '' }}>{{ $kat->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                    <input type="text" name="periode" value="{{ old('periode', $dataset->periode) }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="4" {{ request('revisi') ? '' : 'required' }} class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">{{ old('deskripsi', $dataset->deskripsi) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">File Dataset @if(request('revisi'))<span class="text-red-500">* (wajib di upload untuk revisi)</span>@endif</label>
                    @if(!request('revisi'))
                        @if($dataset->file_path)
                            <p class="text-sm text-gray-500 mb-2">File saat ini: {{ $dataset->file_path }}</p>
                        @endif
                    @endif
                    <input type="file" @unless(\App\Services\UnggahDokumen::langsung()) name="file" @endunless accept=".pdf,.xls,.xlsx,.zip,.csv,.json" {{ request('revisi') ? 'required' : '' }}
                        class="w-full text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:bg-primary-50 file:text-primary-700 file:font-medium">
                    @if(!request('revisi'))
                        <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak ingin mengubah file.</p>
                    @endif
                </div>
            </div>

            @include('internal.partials.unggah-langsung', ['tujuan' => request('revisi') ? 'katalog-revisi' : 'katalog-edit', 'target' => $dataset->id])
            <div class="flex gap-3">
                <button type="submit" class="bg-primary-500 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-primary-600 transition shadow">{{ request('revisi') ? 'Simpan Revisi' : 'Update Dataset' }}</button>
                <a href="{{ route('internal.katalog.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">Batal</a>
            </div>
        </form>
    </div>
@endsection
