@extends('layouts.public')

@section('title', 'Verifikasi OTP - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="max-w-lg mx-auto px-4 py-12">
        <div class="bg-white rounded-xl shadow-sm border p-8"
            x-data="{
                timer: {{ $durasiOtpDetik }},
                display: '00:00',
                expired: {{ $durasiOtpDetik > 0 ? 'false' : 'true' }},
                resendTimer: {{ $durasiKirimUlangDetik ?? 60 }},
                resendSubmitting: false
            }"
            x-init="
                display = String(Math.floor(timer / 60)).padStart(2, '0') + ':' + String(timer % 60).padStart(2, '0');
                setInterval(() => {
                    if (timer > 0) {
                        timer--;
                        display = String(Math.floor(timer / 60)).padStart(2, '0') + ':' + String(timer % 60).padStart(2, '0');
                    } else {
                        expired = true;
                    }
                    if (resendTimer > 0) {
                        resendTimer--;
                    }
                }, 1000)
            ">
            <h1 class="text-2xl font-bold text-gray-800 text-center">Verifikasi Kode OTP</h1>
            <p class="text-gray-500 text-center mt-2">
                Kode dikirim melalui {{ $kanalOtp }} ke nomor <strong>{{ $nomorTersamar }}</strong>
                untuk {{ $modeOtp === 'masuk' ? 'masuk' : 'menyelesaikan pendaftaran' }}.
            </p>

            <form method="POST" action="{{ route('otp.verifikasi') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="kode_otp" class="block text-sm font-medium text-gray-700 mb-1">Kode OTP 6 Digit</label>
                    <input id="kode_otp" type="text" name="kode_otp" maxlength="6" inputmode="numeric"
                        pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus
                        class="w-full text-center text-2xl tracking-[0.5em] px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                        placeholder="000000"
                        @error('kode_otp') aria-invalid="true" aria-describedby="kode-otp-error" @enderror>
                    @error('kode_otp') <p id="kode-otp-error" class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" x-bind:disabled="expired"
                    class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Verifikasi & Lanjutkan
                </button>
            </form>

            <div class="mt-4 text-center">
                <p x-show="!expired" class="js-only text-sm text-gray-500">Kode berlaku selama <span x-text="display" class="font-mono font-bold text-primary-500"></span></p>
                <p x-show="expired" x-cloak role="status" aria-live="polite" class="js-only text-sm text-red-500">Waktu kode habis. Silakan kirim ulang OTP.</p>
                <noscript><p class="text-sm text-gray-500">Masa berlaku kode tetap diperiksa oleh sistem saat formulir dikirim.</p></noscript>
            </div>

            <div class="mt-4 flex items-center justify-center gap-4 text-sm">
                <form method="POST" action="{{ route('otp.kirim-ulang') }}"
                    @submit="if (resendTimer > 0 || resendSubmitting) { $event.preventDefault() } else { resendSubmitting = true }">
                    @csrf
                    <button type="submit" :disabled="resendTimer > 0 || resendSubmitting"
                        class="text-primary-500 font-semibold hover:underline focus-visible:ring-2 focus-visible:ring-primary-500 rounded disabled:text-gray-400 disabled:no-underline disabled:cursor-not-allowed">
                        <span x-show="resendSubmitting" class="js-only">Mengirim...</span>
                        <span x-show="!resendSubmitting && resendTimer > 0" class="js-only">Kirim ulang dalam <span x-text="resendTimer"></span> detik</span>
                        <span x-show="!resendSubmitting && resendTimer <= 0">Kirim Ulang OTP</span>
                    </button>
                </form>
                <a href="{{ $modeOtp === 'masuk' ? route('pemohon.masuk') : route('pemohon.daftar') }}"
                    class="text-gray-500 hover:underline">Kembali</a>
            </div>
        </div>
    </div>
@endsection
