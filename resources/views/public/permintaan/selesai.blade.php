@extends('layouts.public')

@section('title', 'Permintaan Berhasil Diajukan - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="max-w-lg mx-auto px-4 py-16 text-center">
        <div class="bg-white rounded-xl shadow-sm border p-8">
            <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-10 h-10" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mt-6">Permintaan Anda Telah Diajukan</h1>
            <p class="text-gray-500 mt-2">Simpan nomor tiket berikut untuk melacak status permintaan Anda.</p>

            <div class="mt-6 bg-gray-50 rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-1">Nomor Tiket</p>
                <p class="text-xl sm:text-3xl font-extrabold tracking-wider text-primary-600 font-mono break-all" aria-label="Nomor tiket {{ $permintaan->nomor_tiket }}">{{ $permintaan->nomor_tiket }}</p>
            </div>

            <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 p-4 rounded text-sm text-left">
                <p class="font-medium">Informasi Penting:</p>
                <ul class="list-disc list-inside mt-1">
                    <li>Gunakan nomor tiket untuk cek status permintaan</li>
                    <li>Proses verifikasi maksimal 3 hari kerja</li>
                    <li>Pembaruan status penting dikirim ke email yang terdaftar, jika tersedia</li>
                    <li>Kode masuk akun dikirim ke nomor HP yang terdaftar</li>
                </ul>
            </div>

            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('pemohon.permintaan.index') }}" class="bg-primary-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 transition">Lihat Permintaan Saya</a>
                <a href="{{ route('cek-status') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2 transition">Cek Status dengan Tiket</a>
            </div>
            <a href="{{ route('beranda') }}" class="inline-block mt-5 text-sm font-semibold text-primary-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded">Kembali ke Beranda</a>
        </div>
    </div>
@endsection
