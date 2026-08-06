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
        @if(!session('pemohon_id'))
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded mb-6">
                <p class="text-yellow-700 text-sm">Anda perlu melakukan verifikasi nomor HP terlebih dahulu sebelum mengajukan permintaan.</p>
                <a href="{{ route('otp.form') }}" class="text-sm text-primary-500 font-semibold hover:underline mt-1 inline-block">Verifikasi Sekarang</a>
            </div>
        @endif

        <form method="POST" action="{{ route('permintaan.store') }}" class="bg-white rounded-xl shadow-sm border p-8 space-y-6">
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

            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-700 mb-2">Informasi Pemohon</h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Nama:</span>
                        <span class="font-medium text-gray-800 ml-1">{{ session('pemohon_nama', $pemohon->nama ?? '-') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">No. HP:</span>
                        <span class="font-medium text-gray-800 ml-1">{{ session('otp_nomor', $pemohon->no_hp ?? '-') }}</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Data <span class="text-red-500">*</span></label>
                <input type="text" name="jenis_data" value="{{ old('jenis_data') }}" required placeholder="Contoh: Data Penduduk, Data Ekonomi, dll."
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tujuan Penggunaan <span class="text-red-500">*</span></label>
                <textarea name="tujuan_penggunaan" rows="4" required placeholder="Jelaskan tujuan penggunaan data yang diminta..."
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">{{ old('tujuan_penggunaan') }}</textarea>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Periode Data <span class="text-red-500">*</span></label>
                    <input type="text" name="periode_data" value="{{ old('periode_data') }}" required placeholder="Contoh: 2023-2024"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select name="kategori_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="">Pilih Kategori</option>
                        @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id') == $kat->id ? 'selected' : '' }}>{{ $kat->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 transition shadow">Ajukan Permintaan</button>
        </form>
    </div>
@endsection