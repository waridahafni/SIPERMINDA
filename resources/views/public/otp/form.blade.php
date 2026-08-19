@extends('layouts.public')

@section('title', 'Verifikasi - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="max-w-lg mx-auto px-4 py-12">
        <div class="bg-white rounded-xl shadow-sm border p-8">
            @if($menungguOtp)
                <div x-data="{ timer: {{ $durasiOtpDetik }}, display: '05:00', expired: false }" x-init="display = String(Math.floor(timer / 60)).padStart(2, '0') + ':' + String(timer % 60).padStart(2, '0'); setInterval(() => { if (timer > 0) { timer--; let m = Math.floor(timer / 60); let s = timer % 60; display = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0') } else { expired = true } }, 1000)">
                    <h2 class="text-xl font-bold text-gray-800 text-center">Verifikasi Kode OTP</h2>
                    <p class="text-gray-500 text-center mt-2">Kode OTP telah dikirim melalui {{ $kanalOtp }} ke nomor <strong>{{ $nomorTersamar }}</strong></p>

                    <form method="POST" action="{{ route('otp.verifikasi') }}" class="mt-6 space-y-4">
                        @csrf
                        <input type="hidden" name="no_hp" value="{{ old('no_hp', session('otp_pemohon.no_hp') ?? session('otp_nomor')) }}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Masukkan Kode OTP</label>
                            <input type="text" name="kode_otp" maxlength="6" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required
                                class="w-full text-center text-2xl tracking-[0.5em] px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" placeholder="000000">
                            @error('kode_otp') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" x-bind:disabled="expired" class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Verifikasi
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <p x-show="!expired" class="text-sm text-gray-500">Kode OTP berlaku selama <span x-text="display" class="font-mono font-bold text-primary-500"></span></p>
                        <p x-show="expired" class="text-sm text-red-500">Waktu habis. Silakan kirim ulang OTP.</p>
                    </div>

                    <div class="mt-4 text-center">
                        <form method="POST" action="{{ route('otp.kirim-ulang') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-primary-500 hover:underline">Kirim Ulang OTP</button>
                        </form>
                    </div>
                </div>
            @else
                <h2 class="text-xl font-bold text-gray-800 text-center">Verifikasi Nomor HP</h2>
                <p class="text-gray-500 text-center mt-2">Masukkan nomor WhatsApp aktif untuk menerima kode OTP.</p>

                <form method="POST" action="{{ route('otp.kirim') }}" class="mt-6 space-y-4" x-data="{ jenisPemohon: @js(old('jenis_pemohon', 'publik')) }">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP</label>
                        <input type="text" name="no_hp" value="{{ old('no_hp', session('otp_pemohon.no_hp') ?? session('otp_nomor')) }}" required placeholder="08xxxxxxxxxx"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        @error('no_hp') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pemohon</label>
                        <select name="jenis_pemohon" x-model="jenisPemohon" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                            <option value="publik" {{ old('jenis_pemohon') == 'publik' ? 'selected' : '' }}>Publik / Perorangan</option>
                            <option value="instansi" {{ old('jenis_pemohon') == 'instansi' ? 'selected' : '' }}>Instansi / Organisasi</option>
                        </select>
                        @error('jenis_pemohon') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div id="field-instansi" x-show="jenisPemohon === 'instansi'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Instansi</label>
                        <input type="text" name="nama_instansi" value="{{ old('nama_instansi') }}" placeholder="Nama instansi/organisasi"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        @error('nama_instansi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pemohon</label>
                        <input type="text" name="nama" value="{{ old('nama') }}" required placeholder="Nama lengkap"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        @error('nama') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-gray-400">(opsional)</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="contoh@email.com"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 transition">Kirim OTP via WhatsApp</button>
                </form>
            @endif
        </div>
    </div>
@endsection
