@extends('layouts.public')

@section('title', 'Lengkapi Pendaftaran - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="max-w-lg mx-auto px-4 py-12">
        <div class="bg-white rounded-xl shadow-sm border p-8">
            <h1 class="text-2xl font-bold text-gray-800 text-center">Lengkapi Pendaftaran</h1>
            <p class="text-gray-500 text-center mt-2">Nomor <strong>{{ $nomorTersamar }}</strong> sudah terverifikasi. Lengkapi profil Anda untuk membuat akun.</p>

            <form method="POST" action="{{ route('pemohon.daftar.lengkapi.simpan') }}" class="mt-6 space-y-4"
                x-data="{ jenisPemohon: @js(old('jenis_pemohon', 'publik')), submitting: false }"
                @submit="if (submitting) { $event.preventDefault() } else { submitting = true }">
                @csrf

                @include('public.auth._profil')

                <button type="submit" :disabled="submitting" aria-live="polite"
                    class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!submitting">Simpan & Masuk</span>
                    <span x-show="submitting" x-cloak class="js-only">Menyimpan...</span>
                </button>
            </form>
        </div>
    </div>
@endsection
