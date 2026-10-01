@extends('layouts.public')

@section('title', 'Daftar Pemohon - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-10 sm:py-14">
        <div class="grid overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl lg:grid-cols-[0.85fr_1.15fr]">

            {{-- Informasi --}}
            <div class="bg-gradient-to-br from-secondary-800 to-primary-600 p-8 text-white sm:p-10">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-200">
                    Mulai dari sini
                </p>

                <h1 class="mt-4 text-3xl font-extrabold leading-tight">
                    Ajukan data dengan lebih mudah
                </h1>

                <p class="mt-4 text-sm leading-relaxed text-primary-100">
                    Buat profil pemohon sekali, lalu gunakan untuk mengajukan,
                    memantau proses, dan mengakses hasil permintaan data Anda.
                </p>

                <div class="mt-7 space-y-3 text-sm text-primary-100">
                    <div class="flex items-start gap-3">
                        <span class="mt-1 inline-block h-2 w-2 shrink-0 rounded-full bg-white/80"></span>
                        <span>Gunakan nomor WhatsApp aktif untuk menerima kode OTP.</span>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="mt-1 inline-block h-2 w-2 shrink-0 rounded-full bg-white/80"></span>
                        <span>Isi data sesuai identitas dan informasi Anda.</span>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="mt-1 inline-block h-2 w-2 shrink-0 rounded-full bg-white/80"></span>
                        <span>Data profil dapat digunakan kembali untuk permintaan berikutnya.</span>
                    </div>
                </div>

                <a
                    href="{{ route('beranda') }}"
                    class="mt-8 inline-flex text-sm font-semibold text-white underline underline-offset-4 hover:text-primary-100"
                >
                    &larr; Kembali ke beranda
                </a>
            </div>

            {{-- Form --}}
            <div class="p-6 sm:p-10">

                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        Daftar Pemohon
                    </h2>

                    <p class="mt-2 text-sm leading-relaxed text-gray-500">
                        Lengkapi data berikut untuk membuat akun SIPERMINDA.
                        Kolom bertanda
                        <span class="font-semibold text-red-500">*</span>
                        wajib diisi.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('pemohon.daftar.kirim-otp') }}"
                    class="mt-8 space-y-6"
                    x-data="{
                        jenisPemohon: @js(old('jenis_pemohon', '')),
                        submitting: false
                    }"
                    @submit="
                        if (submitting) {
                            $event.preventDefault()
                        } else {
                            submitting = true
                        }
                    "
                >
                    @csrf

                    {{-- Nomor WhatsApp --}}
                    <div>
                        <label
                            for="no_hp"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Nomor WhatsApp
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="no_hp"
                            type="tel"
                            name="no_hp"
                            value="{{ old('no_hp') }}"
                            required
                            inputmode="tel"
                            autocomplete="tel"
                            placeholder="Contoh: 0812 3456 7890"
                            class="w-full rounded-lg border px-4 py-2.5 outline-none transition
                                focus:ring-2 focus:ring-primary-500/20
                                @error('no_hp')
                                    border-red-400 focus:border-red-400
                                @else
                                    border-gray-300 focus:border-primary-500
                                @enderror"
                            @error('no_hp')
                                aria-invalid="true"
                                aria-describedby="no-hp-error"
                            @enderror
                        >

                        <p class="mt-1.5 text-xs leading-relaxed text-gray-500">
                            Kode OTP akan dikirim ke nomor WhatsApp ini.
                            Pastikan nomor masih aktif dan dapat menerima pesan.
                        </p>

                        @error('no_hp')
                            <p
                                id="no-hp-error"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    @include('public.auth._profil')
                    <p class="text-sm text-gray-600">Setelah nomor diverifikasi dengan OTP, Anda akan membuat password. Login berikutnya cukup memakai nomor HP dan password.</p>

                    {{-- Info OTP --}}
                    <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                        <p class="text-sm leading-relaxed text-gray-600">
                            Setelah menekan tombol di bawah, kami akan mengirimkan
                            <span class="font-semibold text-gray-700">kode OTP melalui WhatsApp</span>
                            untuk memverifikasi nomor Anda.
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="submitting"
                        aria-live="polite"
                        class="w-full rounded-lg bg-primary-500 py-3 font-semibold text-white transition
                            hover:bg-primary-600
                            focus-visible:outline-none
                            focus-visible:ring-2
                            focus-visible:ring-primary-500
                            focus-visible:ring-offset-2
                            disabled:cursor-not-allowed
                            disabled:opacity-50"
                    >
                        <span x-show="!submitting">
                            Daftar & Kirim OTP
                        </span>

                        <span
                            x-show="submitting"
                            x-cloak
                        >
                            Mengirim OTP...
                        </span>
                    </button>
                </form>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <p class="text-center text-sm text-gray-500">
                        Sudah punya akun?

                        <a
                            href="{{ route('pemohon.masuk') }}"
                            class="font-semibold text-primary-500 hover:text-primary-600 hover:underline"
                        >
                            Masuk Pemohon
                        </a>
                    </p>
                </div>

            </div>
        </div>
    </div>
@endsection
