@extends('layouts.public')

@section('title', 'Cek Status Permintaan - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-8">
            <h1 class="text-2xl font-bold text-gray-800">Cek Status Permintaan</h1>
            <p class="text-gray-500 mt-1">Masukkan nomor tiket dan nomor WhatsApp yang digunakan saat pengajuan.</p>
        </div>
    </div>

    <div class="max-w-md mx-auto px-4 py-12">
        <div class="bg-white rounded-xl shadow-sm border p-8">
            <form method="POST" action="{{ route('cek-status.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="nomor_tiket" class="block text-sm font-medium text-gray-700 mb-1">Nomor Tiket</label>
                    <input id="nomor_tiket" type="text" name="nomor_tiket" value="{{ old('nomor_tiket') }}" required autocomplete="off" autocapitalize="characters" placeholder="Contoh: BPS/PD/2026/00001"
                        @error('nomor_tiket') aria-invalid="true" aria-describedby="nomor_tiket-error" @enderror
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                    @error('nomor_tiket') <p id="nomor_tiket-error" class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="no_hp" class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp</label>
                    <input id="no_hp" type="tel" name="no_hp" value="{{ old('no_hp', session('pemohon_otp')) }}" required inputmode="tel" autocomplete="tel" placeholder="Contoh: 081234567890"
                        @error('no_hp') aria-invalid="true" aria-describedby="no_hp-error" @enderror
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                    @error('no_hp') <p id="no_hp-error" class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 transition shadow">Cek Status</button>
            </form>

            <p class="mt-5 text-center text-xs leading-relaxed text-gray-500">Status dapat dicek tanpa masuk. Untuk mengunduh file hasil, Anda harus masuk ke akun pemohon yang mengajukan permintaan.</p>
        </div>
    </div>
@endsection
