@extends('layouts.public')

@section('title', 'Masuk Pemohon - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-10 sm:py-14">

        <div class="grid overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl lg:grid-cols-[0.85fr_1.15fr]">

            {{-- Informasi --}}
            <div class="bg-gradient-to-br from-secondary-800 to-primary-600 p-8 text-white sm:p-10">

                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-200">
                    Portal Pemohon
                </p>

                <h1 class="mt-4 text-3xl font-extrabold leading-tight">
                    Selamat datang kembali
                </h1>

                <p class="mt-4 text-sm leading-relaxed text-primary-100">
                    Masuk untuk melihat riwayat permintaan dan memantau data yang sedang diproses.
                </p>

                <a
                    href="{{ route('beranda') }}"
                    class="mt-8 inline-flex text-sm font-semibold text-white underline underline-offset-4 hover:text-primary-100"
                >
                    &larr; Kembali ke beranda
                </a>

            </div>


            {{-- Form masuk --}}
            <div class="p-6 sm:p-10">

                <h2 class="text-2xl font-bold text-gray-800">
                    Masuk Pemohon
                </h2>

                <p class="mt-2 text-sm leading-relaxed text-gray-500">
                    Masukkan nomor WhatsApp yang terdaftar.
                    Kami akan mengirimkan kode OTP untuk memverifikasi akun Anda.
                </p>


                <form
                    method="POST"
                    action="{{ route('pemohon.masuk.kirim-otp') }}"
                    class="mt-8 space-y-5"

                    x-data="{
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

                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none transition
                                focus:border-primary-500
                                focus:ring-2
                                focus:ring-primary-500/20
                                @error('no_hp') border-red-400 @enderror"

                            @error('no_hp')
                                aria-invalid="true"
                                aria-describedby="no-hp-error"
                            @enderror
                        >


                        <p class="mt-1.5 text-xs text-gray-500">
                            Pastikan nomor WhatsApp masih aktif dan dapat menerima pesan.
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


                    <button
                        type="submit"
                        :disabled="submitting"
                        aria-live="polite"

                        class="w-full rounded-lg bg-primary-500 py-3 font-semibold text-white transition
                            hover:bg-primary-600
                            focus-visible:ring-2
                            focus-visible:ring-primary-500
                            focus-visible:ring-offset-2
                            disabled:cursor-not-allowed
                            disabled:opacity-50"
                    >

                        <span x-show="!submitting">
                            Kirim Kode OTP
                        </span>

                        <span
                            x-show="submitting"
                            x-cloak
                        >
                            Mengirim OTP...
                        </span>

                    </button>

                </form>


                <p class="mt-6 text-center text-sm text-gray-500">
                    Belum punya akun?

                    <a
                        href="{{ route('pemohon.daftar') }}"
                        class="font-semibold text-primary-500 hover:underline"
                    >
                        Daftar Pemohon
                    </a>
                </p>


                <p class="mt-3 text-center text-xs text-gray-400">
                    Akses petugas tersedia melalui menu masuk khusus pegawai BPS.
                </p>

            </div>

        </div>

    </div>
@endsection