@extends('layouts.public')
@section('title', 'Pemulihan Akun Pemohon')
@section('content')
    <div class="mx-auto max-w-lg px-4 py-10">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold">Buat atau Pulihkan Password</h1>
            <p class="mt-3 text-sm text-gray-600">Untuk akun lama yang belum punya password atau jika Anda lupa password, verifikasi nomor WhatsApp melalui OTP. Setelah itu, buat password untuk login berikutnya.</p>
            <form method="POST" action="{{ route('pemohon.masuk.kirim-otp') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="no_hp" class="block text-sm font-medium">Nomor WhatsApp</label>
                    <input id="no_hp" name="no_hp" type="tel" autocomplete="tel" required maxlength="20" value="{{ old('no_hp') }}" class="mt-1 w-full rounded-lg border p-3">
                    @error('no_hp') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>
                <button class="w-full rounded-lg bg-primary-600 py-3 font-semibold text-white">Kirim OTP Verifikasi</button>
            </form>
            <a href="{{ route('pemohon.masuk') }}" class="mt-5 inline-block text-sm text-primary-700 underline">Kembali ke login password</a>
        </div>
    </div>
@endsection
