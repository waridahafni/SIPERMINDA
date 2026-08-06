@extends('layouts.public')

@section('title', 'Cek Status Permintaan - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-8">
            <h1 class="text-2xl font-bold text-gray-800">Cek Status Permintaan</h1>
            <p class="text-gray-500 mt-1">Masukkan nomor tiket dan nomor HP untuk melihat status permintaan data Anda.</p>
        </div>
    </div>

    <div class="max-w-md mx-auto px-4 py-12">
        <div class="bg-white rounded-xl shadow-sm border p-8">
            <form method="POST" action="{{ route('cek-status.post') }}" class="space-y-5">
                @csrf

                @if(session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded text-sm">{{ session('error') }}</div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Tiket</label>
                    <input type="text" name="nomor_tiket" value="{{ old('nomor_tiket') }}" required placeholder="Masukkan nomor tiket"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                    @error('nomor_tiket') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP</label>
                    <input type="text" name="no_hp" value="{{ old('no_hp') }}" required placeholder="Nomor HP saat pendaftaran"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                    @error('no_hp') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 transition shadow">Cek Status</button>
            </form>
        </div>
    </div>
@endsection