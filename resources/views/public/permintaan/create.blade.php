@extends('layouts.public')

@section('title', 'Ajukan Permintaan Data - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-8">
            <h1 class="text-2xl font-bold text-gray-800">Ajukan Permintaan Data</h1>
            <p class="text-gray-500 mt-1">Isi form berikut untuk mengajukan permintaan data statistik.</p>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-8">
        <form method="POST" action="{{ route('permintaan.store') }}" class="bg-white rounded-xl shadow-sm border p-5 sm:p-8 space-y-6"
            x-data="{ submitting: false }"
            @submit="if (submitting) { $event.preventDefault() } else { submitting = true }">
            @csrf
            <input type="hidden" name="idempotensi_token" value="{{ $idempotensiToken }}">

            @if($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert" aria-labelledby="form-error-title">
                    <p id="form-error-title" class="font-semibold text-sm">Periksa kembali data berikut:</p>
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-700 mb-2">Informasi Pemohon</h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Nama:</span>
                        <span class="font-medium text-gray-800 ml-1">{{ $pemohon->nama }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Nomor WhatsApp:</span>
                        <span class="font-medium text-gray-800 ml-1">{{ $pemohon->no_hp }}</span>
                    </div>
                </div>
            </div>

            <div>
                <label for="jenis_data" class="block text-sm font-medium text-gray-700 mb-1">Jenis Data <span class="text-red-500" aria-hidden="true">*</span><span class="sr-only"> (wajib)</span></label>
                <input id="jenis_data" type="text" name="jenis_data" value="{{ old('jenis_data') }}" required placeholder="Contoh: Data Penduduk, Data Ekonomi, dll."
                    @error('jenis_data') aria-invalid="true" aria-describedby="jenis_data-error" @enderror
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                @error('jenis_data') <p id="jenis_data-error" class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="tujuan_penggunaan" class="block text-sm font-medium text-gray-700 mb-1">Tujuan Penggunaan <span class="text-red-500" aria-hidden="true">*</span><span class="sr-only"> (wajib)</span></label>
                <textarea id="tujuan_penggunaan" name="tujuan_penggunaan" rows="4" required placeholder="Jelaskan tujuan penggunaan data yang diminta..."
                    @error('tujuan_penggunaan') aria-invalid="true" aria-describedby="tujuan_penggunaan-error" @enderror
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">{{ old('tujuan_penggunaan') }}</textarea>
                @error('tujuan_penggunaan') <p id="tujuan_penggunaan-error" class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="periode_data" class="block text-sm font-medium text-gray-700 mb-1">Periode Data <span class="text-red-500" aria-hidden="true">*</span><span class="sr-only"> (wajib)</span></label>
                    <input id="periode_data" type="text" name="periode_data" value="{{ old('periode_data') }}" required placeholder="Contoh: 2023-2024"
                        @error('periode_data') aria-invalid="true" aria-describedby="periode_data-error" @enderror
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                    @error('periode_data') <p id="periode_data-error" class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="kategori_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select id="kategori_id" name="kategori_id" @error('kategori_id') aria-invalid="true" aria-describedby="kategori_id-error" @enderror
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="">Pilih Kategori</option>
                        @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id') == $kat->id ? 'selected' : '' }}>{{ $kat->nama }}</option>
                        @endforeach
                    </select>
                    @error('kategori_id') <p id="kategori_id-error" class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" :disabled="submitting" aria-live="polite"
                class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 transition shadow disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!submitting">Ajukan Permintaan</span>
                <span x-show="submitting" x-cloak class="js-only">Mengajukan...</span>
            </button>
        </form>
    </div>
@endsection
