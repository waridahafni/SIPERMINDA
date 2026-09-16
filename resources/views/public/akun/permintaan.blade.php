@extends('layouts.public')

@section('title', 'Permintaan Saya - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-5xl mx-auto px-4 py-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Permintaan Saya</h1>
                <p class="text-gray-500 mt-1">Riwayat permintaan data milik {{ $pemohon->nama }}.</p>
            </div>
            <a href="{{ route('permintaan.create') }}"
                class="inline-flex justify-center bg-primary-500 text-white px-5 py-3 rounded-lg font-semibold hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 transition">
                Ajukan Permintaan Baru
            </a>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 py-8">
        @if($permintaan->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border p-10 text-center">
                <h2 class="text-lg font-semibold text-gray-800">Belum ada permintaan</h2>
                <p class="text-gray-500 mt-2">Permintaan data yang Anda ajukan akan muncul di halaman ini.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($permintaan as $item)
                    @php
                        $labelStatus = match($item->status) {
                            'diajukan' => 'Diajukan',
                            'disetujui_petugas' => 'Disetujui Petugas',
                            'menunggu_info_pemohon' => 'Perlu Jawaban Anda',
                            'ditolak' => 'Ditolak',
                            'data_siap' => 'Data Siap',
                            'selesai' => 'Selesai',
                            default => str_replace('_', ' ', ucfirst($item->status)),
                        };
                        $warnaStatus = match($item->status) {
                            'ditolak' => 'bg-red-100 text-red-700',
                            'data_siap', 'selesai' => 'bg-green-100 text-green-700',
                            'menunggu_info_pemohon' => 'bg-amber-100 text-amber-800',
                            'disetujui_petugas' => 'bg-blue-100 text-blue-700',
                            default => 'bg-yellow-100 text-yellow-700',
                        };
                    @endphp
                    <article class="bg-white rounded-xl shadow-sm border p-6">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div>
                                <p class="font-mono text-sm font-bold text-primary-600">{{ $item->nomor_tiket }}</p>
                                <h2 class="text-lg font-semibold text-gray-800 mt-1">{{ $item->jenis_data }}</h2>
                                <p class="text-sm text-gray-500 mt-1">Diajukan {{ $item->created_at->format('d M Y H:i') }}</p>
                            </div>
                            <span class="self-start text-xs font-bold px-3 py-1 rounded-full {{ $warnaStatus }}">{{ $labelStatus }}</span>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
                            <span class="text-gray-500">Periode: <strong class="text-gray-700">{{ $item->periode_data ?: '-' }}</strong></span>
                            @if($item->kategori)
                                <span class="text-gray-500">Kategori: <strong class="text-gray-700">{{ $item->kategori->nama }}</strong></span>
                            @endif
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('pemohon.permintaan.show', $item) }}"
                                class="inline-flex border px-4 py-2.5 rounded-lg font-semibold focus-visible:ring-2 focus-visible:ring-offset-2 transition {{ $item->status === 'menunggu_info_pemohon' ? 'border-amber-500 bg-amber-50 text-amber-800 hover:bg-amber-100 focus-visible:ring-amber-500' : 'border-primary-500 text-primary-600 hover:bg-primary-50 focus-visible:ring-primary-500' }}">
                                {{ $item->status === 'menunggu_info_pemohon' ? 'Jawab Info Tambahan' : 'Lihat Detail' }}
                            </a>
                            @if(in_array($item->status, ['data_siap', 'selesai'], true) && $item->file_hasil_path)
                                <a href="{{ route('permintaan.unduh', $item) }}"
                                    class="inline-flex bg-green-600 text-white px-4 py-2.5 rounded-lg font-semibold hover:bg-green-700 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-green-600 transition">
                                    Unduh Hasil
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $permintaan->links() }}</div>
        @endif
    </div>
@endsection
