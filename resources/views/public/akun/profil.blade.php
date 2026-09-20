@extends('layouts.public')

@section('title', 'Profil Saya - SIPERMINDA')

@section('content')
    <div class="bg-white border-b shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="text-sm font-semibold uppercase tracking-wider text-primary-600">Akun Pemohon</p><h1 class="mt-1 text-2xl font-bold text-gray-800">Profil Saya</h1><p class="mt-2 text-gray-500">Informasi yang digunakan untuk layanan permintaan data.</p></div>
            <a href="{{ route('pemohon.profil.edit') }}" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-5 py-3 font-semibold text-white hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">Edit Profil</a>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-8 sm:px-6">
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" aria-labelledby="data-profil">
            <div class="flex items-center gap-4 border-b border-gray-100 bg-gray-50 px-6 py-5">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-600 text-lg font-bold text-white">{{ mb_strtoupper(mb_substr($pemohon->nama, 0, 1)) }}</span>
                <div><h2 id="data-profil" class="font-bold text-gray-800">{{ $pemohon->nama }}</h2><p class="text-sm text-gray-500">Pemohon terverifikasi</p></div>
            </div>
            <dl class="grid sm:grid-cols-2">
                @php($dataProfil = [
                    'Nama Lengkap' => $pemohon->nama,
                    'Nomor WhatsApp' => $pemohon->no_hp,
                    'Email' => $pemohon->email ?: '-',
                    'Kategori Pemohon' => $pemohon->jenis_pemohon === 'instansi' ? 'Instansi / Organisasi' : 'Publik / Perorangan',
                    'Asal Instansi/Lembaga' => $pemohon->nama_instansi ?: '-',
                    'Provinsi' => $pemohon->provinsi ?: '-',
                    'Kabupaten/Kota' => $pemohon->kabupaten_kota ?: '-',
                    'Alamat Lengkap' => $pemohon->alamat_lengkap ?: '-',
                ])
                @foreach($dataProfil as $label => $nilai)
                    <div class="border-b border-gray-100 px-6 py-4 sm:odd:border-r"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</dt><dd class="mt-1 text-sm text-gray-800 whitespace-pre-line">{{ $nilai }}</dd></div>
                @endforeach
                <div class="px-6 py-4 sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status verifikasi nomor WhatsApp</dt><dd class="mt-2 inline-flex rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-700">{{ $pemohon->no_hp_verified_at ? 'Terverifikasi' : 'Belum terverifikasi' }}</dd></div>
            </dl>
        </section>
    </div>
@endsection
