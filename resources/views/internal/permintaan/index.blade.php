@extends('layouts.internal')

@section('title', 'Permintaan Masuk')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Permintaan Masuk</h1>
            <p class="text-gray-500 text-sm">Kelola semua permintaan data dari pemohon.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="border-b px-4 py-3" x-data="{ tab: '{{ request('status', 'semua') }}' }">
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('internal.permintaan.index') }}" @click="tab = 'semua'" class="px-3 py-1.5 rounded-lg font-medium transition" :class="tab === 'semua' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Semua</a>
                <a href="{{ route('internal.permintaan.index', ['status' => 'diajukan']) }}" @click="tab = 'diajukan'" class="px-3 py-1.5 rounded-lg font-medium transition" :class="tab === 'diajukan' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Diajukan</a>
                <a href="{{ route('internal.permintaan.index', ['status' => 'diverifikasi_staf']) }}" @click="tab = 'diverifikasi_staf'" class="px-3 py-1.5 rounded-lg font-medium transition" :class="tab === 'diverifikasi_staf' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Diverifikasi</a>
                <a href="{{ route('internal.permintaan.index', ['status' => 'disetujui_kasi']) }}" @click="tab = 'disetujui_kasi'" class="px-3 py-1.5 rounded-lg font-medium transition" :class="tab === 'disetujui_kasi' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Disetujui Kasi</a>
                <a href="{{ route('internal.permintaan.index', ['status' => 'disetujui_kabid']) }}" @click="tab = 'disetujui_kabid'" class="px-3 py-1.5 rounded-lg font-medium transition" :class="tab === 'disetujui_kabid' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Disetujui Kabid</a>
                <a href="{{ route('internal.permintaan.index', ['status' => 'data_siap']) }}" @click="tab = 'data_siap'" class="px-3 py-1.5 rounded-lg font-medium transition" :class="tab === 'data_siap' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Data Siap</a>
                <a href="{{ route('internal.permintaan.index', ['status' => 'ditolak']) }}" @click="tab = 'ditolak'" class="px-3 py-1.5 rounded-lg font-medium transition" :class="tab === 'ditolak' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Ditolak</a>
            </div>
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
                        <th class="text-center px-6 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($permintaan as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-mono text-primary-600 font-medium">{{ $p->nomor_tiket }}</td>
                            <td class="px-6 py-3">{{ $p->pemohon->nama ?? '-' }}<br><span class="text-xs text-gray-400">{{ $p->pemohon->no_hp ?? '' }}</span></td>
                            <td class="px-6 py-3">{{ $p->jenis_data }}</td>
                            <td class="px-6 py-3">
                                @php
                                    $badge = match($p->status) {
                                        'diajukan' => 'yellow',
                                        'diverifikasi_staf' => 'blue',
                                        'disetujui_kasi' => 'indigo',
                                        'disetujui_kabid' => 'purple',
                                        'ditolak' => 'red',
                                        'data_siap' => 'green',
                                        'selesai' => 'teal',
                                        default => 'gray'
                                    };
                                @endphp
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-{{ $badge }}-100 text-{{ $badge }}-800">{{ str_replace('_', ' ', ucfirst($p->status)) }}</span>
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $p->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-3 text-center">
                                <a href="{{ route('internal.permintaan.show', $p) }}" class="text-primary-500 hover:underline font-medium text-xs">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Tidak ada permintaan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($permintaan->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $permintaan->links() }}
            </div>
        @endif
    </div>
@endsection