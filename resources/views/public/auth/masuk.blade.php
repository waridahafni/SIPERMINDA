@extends('layouts.public')

@section('title', 'Masuk Pemohon - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="max-w-lg mx-auto px-4 py-12">
        <div class="bg-white rounded-xl shadow-sm border p-8">
            <h1 class="text-2xl font-bold text-gray-800 text-center">Masuk Pemohon</h1>
            <p class="text-gray-500 text-center mt-2">Masukkan nomor WhatsApp. Kami akan mengirim kode OTP tanpa password.</p>

            <form method="POST" action="{{ route('pemohon.masuk.kirim-otp') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="no_hp" class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp</label>
                    <input id="no_hp" type="tel" name="no_hp" value="{{ old('no_hp') }}" required
                        inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                        @error('no_hp') aria-invalid="true" aria-describedby="no-hp-error" @enderror>
                    @error('no_hp') <p id="no-hp-error" class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 transition">
                    Masuk dengan OTP
                </button>
            </form>

            <p class="text-sm text-gray-500 text-center mt-6">
                Belum punya akun?
                <a href="{{ route('pemohon.daftar') }}" class="font-semibold text-primary-500 hover:underline">Daftar Pemohon</a>
            </p>
            <p class="text-xs text-gray-400 text-center mt-3">Masuk Petugas tersedia khusus pegawai BPS melalui menu terpisah.</p>
        </div>
    </div>
@endsection
