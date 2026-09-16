@extends('layouts.internal')

@section('title', 'Dashboard')

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalPermintaan ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Total Permintaan</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ ($perStatus['diajukan'] ?? 0) + ($perStatus['disetujui_petugas'] ?? 0) + ($perStatus['menunggu_info_pemohon'] ?? 0) }}</p>
                    <p class="text-sm text-gray-500">Diproses</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ ($perStatus['data_siap'] ?? 0) + ($perStatus['selesai'] ?? 0) }}</p>
                    <p class="text-sm text-gray-500">Selesai</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ $perStatus['ditolak'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Ditolak</p>
                </div>
            </div>
        </div>
    </div>

    <section class="mt-8 rounded-xl border border-orange-200 bg-orange-50 p-5" aria-labelledby="sla-layanan">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 id="sla-layanan" class="font-semibold text-orange-900">Perhatian SLA Layanan</h3>
                <p class="mt-1 text-sm text-orange-800">Target penanganan permintaan aktif: maksimal {{ $slaHariKerja }} hari kerja.</p>
            </div>
            <span class="rounded-full bg-orange-600 px-3 py-1 text-sm font-bold text-white">{{ $permintaanMelewatiSla->count() }} perlu ditindaklanjuti</span>
        </div>
        @if($permintaanMelewatiSla->isNotEmpty())
            <ul class="mt-4 divide-y divide-orange-200 rounded-lg border border-orange-200 bg-white text-sm">
                @foreach($permintaanMelewatiSla as $permintaanSla)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                        <span><span class="font-mono font-semibold text-primary-700">{{ $permintaanSla->nomor_tiket }}</span> · {{ $permintaanSla->pemohon->nama }}</span>
                        <a href="{{ route('internal.permintaan.show', $permintaanSla) }}" class="font-semibold text-primary-600 hover:underline">Tinjau</a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-3 text-sm text-orange-800">Tidak ada permintaan aktif yang melewati target saat ini.</p>
        @endif
    </section>

    <div class="mt-8 bg-white rounded-xl shadow-sm border">
        <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Permintaan Terbaru</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium">No Tiket</th>
                        <th class="text-left px-6 py-3 font-medium">Pemohon</th>
                        <th class="text-left px-6 py-3 font-medium">Jenis Data</th>
                        <th class="text-left px-6 py-3 font-medium">Status</th>
                        <th class="text-left px-6 py-3 font-medium">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($permintaanTerbaru ?? [] as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-mono text-primary-600 font-medium">{{ $p->nomor_tiket }}</td>
                            <td class="px-6 py-3">{{ $p->pemohon->nama ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $p->jenis_data }}</td>
                            <td class="px-6 py-3">
                                @php
                                    $badge = match($p->status) {
                                        'diajukan' => 'yellow',
                                        'disetujui_petugas' => 'blue',
                                        'menunggu_info_pemohon' => 'amber',
                                        'ditolak' => 'red',
                                        'data_siap' => 'green',
                                        'selesai' => 'teal',
                                        default => 'gray'
                                    };
                                @endphp
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-{{ $badge }}-100 text-{{ $badge }}-800">
                                    {{ $p->status === 'menunggu_info_pemohon' ? 'Menunggu Info Pemohon' : str_replace('_', ' ', ucfirst($p->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $p->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada permintaan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 bg-white rounded-xl shadow-sm border p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Grafik Permintaan per Bulan</h3>
        <div class="h-64 flex items-end gap-2">
            @php
                $maxVal = max(array_merge(array_column($perBulan->toArray(), 'total'), [1]));
                $charts = $perBulan ?: collect([]);
            @endphp
            <div class="flex items-end gap-3 w-full h-full">
                @forelse($charts as $item)
                    <div class="flex-1 flex flex-col items-center justify-end h-full">
                        <div class="w-full bg-primary-400 rounded-t hover:bg-primary-500 transition cursor-pointer relative group" style="height: {{ ($item->total / $maxVal) * 80 }}%">
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 whitespace-nowrap">{{ $item->total }} permintaan</div>
                        </div>
                        <span class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::createFromFormat('Y-m', $item->bulan)->translatedFormat('M Y') }}</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Belum ada data.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
