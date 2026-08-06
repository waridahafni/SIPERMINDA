@extends('layouts.internal')

@section('title', 'Laporan')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Kinerja Layanan</h1>
            <p class="text-gray-500 text-sm">Ringkasan permintaan, status, dan unduhan data.</p>
        </div>
        <form method="GET" action="{{ route('internal.laporan') }}" class="flex items-center gap-2">
            <input type="month" name="bulan" value="{{ request('bulan') }}"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm">
            <button class="bg-primary-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-primary-600 transition">Filter</button>
            @if(request('bulan'))
                <a href="{{ route('internal.laporan') }}" class="text-sm text-primary-500 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <p class="text-3xl font-bold text-gray-800">{{ $totalPermintaan }}</p>
            <p class="text-sm text-gray-500">Total Permintaan</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <p class="text-3xl font-bold text-gray-800">{{ $totalUnduhan }}</p>
            <p class="text-sm text-gray-500">Total Unduhan</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <p class="text-3xl font-bold text-gray-800">{{ $totalDataset }}</p>
            <p class="text-sm text-gray-500">Dataset Aktif</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <p class="text-3xl font-bold text-gray-800">{{ ($perStatus['data_siap'] ?? 0) + ($perStatus['selesai'] ?? 0) }}</p>
            <p class="text-sm text-gray-500">Permintaan Selesai</p>
        </div>
    </div>

    <div class="mt-8 grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border">
            <div class="px-6 py-4 border-b"><h3 class="font-semibold text-gray-800">Status Permintaan</h3></div>
            <div class="p-6">
                <table class="w-full text-sm">
                    <thead class="text-gray-500"><tr><th class="text-left py-2">Status</th><th class="text-right py-2">Jumlah</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($perStatus as $status => $total)
                            <tr>
                                <td class="py-2">{{ str_replace('_', ' ', ucfirst($status)) }}</td>
                                <td class="py-2 text-right font-semibold">{{ $total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-4 text-center text-gray-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border">
            <div class="px-6 py-4 border-b"><h3 class="font-semibold text-gray-800">Permintaan per Kategori</h3></div>
            <div class="p-6">
                <table class="w-full text-sm">
                    <thead class="text-gray-500"><tr><th class="text-left py-2">Kategori</th><th class="text-right py-2">Jumlah</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($perKategori as $item)
                            <tr>
                                <td class="py-2">{{ $item->kategori->nama ?? '-' }}</td>
                                <td class="py-2 text-right font-semibold">{{ $item->total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-4 text-center text-gray-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-8 bg-white rounded-xl shadow-sm border">
        <div class="px-6 py-4 border-b"><h3 class="font-semibold text-gray-800">Rekap 12 Bulan Terakhir</h3></div>
        <div class="p-6">
            <table class="w-full text-sm">
                <thead class="text-gray-500"><tr><th class="text-left py-2">Bulan</th><th class="text-right py-2">Jumlah Permintaan</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rekapBulanan as $item)
                        <tr>
                            <td class="py-2">{{ \Carbon\Carbon::createFromFormat('Y-m', $item->bulan)->translatedFormat('M Y') }}</td>
                            <td class="py-2 text-right font-semibold">{{ $item->total }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-4 text-center text-gray-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection