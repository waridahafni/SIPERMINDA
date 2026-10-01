@extends('layouts.public')
@section('title', 'Buat Password Pemohon')
@section('content')
    <div class="mx-auto max-w-lg px-4 py-10">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold">Buat Password</h1>
            <p class="mt-3 text-sm text-gray-600">Nomor WhatsApp Anda sudah diverifikasi. Buat password minimal 8 karakter agar login berikutnya tidak perlu OTP. Jika Anda sudah punya password, password lama akan diganti.</p>
            <form method="POST" action="{{ route('pemohon.password.simpan') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="password" class="block text-sm font-medium">Password baru</label>
                    <input id="password" name="password" type="password" required minlength="8" maxlength="72" autocomplete="new-password" class="mt-1 w-full rounded-lg border p-3">
                    @error('password') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium">Ulangi password baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" maxlength="72" autocomplete="new-password" class="mt-1 w-full rounded-lg border p-3">
                </div>
                <button class="w-full rounded-lg bg-primary-600 py-3 font-semibold text-white">Simpan Password dan Lanjutkan</button>
            </form>
        </div>
    </div>
@endsection
