@extends('layouts.public')

@section('title', 'Lengkapi Pendaftaran - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-10 sm:py-14">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">

            {{-- Header --}}
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-500">
                    Tahap Terakhir
                </p>

                <h1 class="mt-2 text-2xl font-bold text-gray-800">
                    Lengkapi Pendaftaran
                </h1>

                <p class="mt-3 text-sm leading-relaxed text-gray-500">
                    Nomor WhatsApp
                    <strong class="font-semibold text-gray-700">
                        {{ $nomorTersamar }}
                    </strong>
                    sudah berhasil diverifikasi.
                </p>

                <p class="mt-1 text-sm leading-relaxed text-gray-500">
                    Lengkapi profil berikut untuk menyelesaikan pembuatan akun SIPERMINDA.
                </p>
            </div>

            {{-- Status verifikasi --}}
            <div class="mt-6 rounded-xl border border-green-100 bg-green-50/70 p-4">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                        ✓
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-gray-700">
                            Nomor WhatsApp berhasil diverifikasi
                        </p>

                        <p class="mt-1 text-xs leading-relaxed text-gray-500">
                            Selanjutnya, isi data profil di bawah ini. Kolom bertanda
                            <span class="font-semibold text-red-500">*</span>
                            wajib diisi.
                        </p>
                    </div>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('pemohon.daftar.lengkapi.simpan') }}"
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

                @include('public.auth._profil')

                {{-- Informasi penyimpanan --}}
                <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                    <p class="text-sm leading-relaxed text-gray-600">
                        Data profil ini akan digunakan untuk mempermudah pengajuan dan
                        pemantauan permintaan data Anda berikutnya.
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
                        Simpan & Masuk
                    </span>

                    <span
                        x-show="submitting"
                        x-cloak
                    >
                        Menyimpan...
                    </span>
                </button>
            </form>
        </div>
    </div>
@endsection