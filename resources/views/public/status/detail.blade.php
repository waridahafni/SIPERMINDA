@extends('layouts.public')

@section('title', 'Status Permintaan - BPS Kabupaten Padang Lawas')

@section('content')
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <a href="{{ route('cek-status') }}" class="text-sm text-primary-500 hover:underline">&larr; Cek Status Lainnya</a>
            <div class="mt-4 flex items-center gap-4 flex-wrap">
                <h1 class="text-2xl font-bold text-gray-800">Status Permintaan</h1>
                <span class="text-xs font-bold px-3 py-1 rounded-full 
                    @switch($permintaan->status)
                        @case('diajukan') bg-yellow-100 text-yellow-800 @break
                        @case('diverifikasi_staf') bg-blue-100 text-blue-800 @break
                        @case('disetujui_kasi') bg-indigo-100 text-indigo-800 @break
                        @case('disetujui_kabid') bg-purple-100 text-purple-800 @break
                        @case('ditolak') bg-red-100 text-red-800 @break
                        @case('menunggu_upload') bg-orange-100 text-orange-800 @break
                        @case('data_siap') bg-green-100 text-green-800 @break
                        @case('selesai') bg-teal-100 text-teal-800 @break
                        @default bg-gray-100 text-gray-800
                    @endswitch">
                    {{ str_replace('_', ' ', ucfirst($permintaan->status)) }}
                </span>
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-sm border p-6 mb-8">
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Nomor Tiket:</span> <span class="font-mono font-bold text-gray-800">{{ $permintaan->nomor_tiket }}</span></div>
                <div><span class="text-gray-500">Tanggal Diajukan:</span> <span class="text-gray-800">{{ $permintaan->created_at->format('d M Y H:i') }}</span></div>
                <div><span class="text-gray-500">Jenis Data:</span> <span class="text-gray-800">{{ $permintaan->jenis_data }}</span></div>
                <div><span class="text-gray-500">Periode:</span> <span class="text-gray-800">{{ $permintaan->periode_data }}</span></div>
            </div>
        </div>

        <h2 class="text-lg font-semibold text-gray-800 mb-4">Riwayat Proses</h2>

        @php
            $logTahap = $permintaan->approvalLog->keyBy('tahap');
            $urutan = [
                'staf' => 'Verifikasi Staf',
                'kasi' => 'Persetujuan Kasi',
                'kabid' => 'Persetujuan Kabid',
            ];
            $semuaSelesai = $permintaan->status === 'data_siap' || $permintaan->status === 'selesai';
        @endphp

        <div class="space-y-0">
            @foreach($urutan as $key => $label)
                @php
                    $log = $logTahap->get($key);
                    $isSelesai = $log && $log->keputusan === 'setuju';
                    $isTolak = $log && $log->keputusan === 'tolak';
                @endphp
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                            @if($isTolak) bg-red-100 text-red-600
                            @elseif($isSelesai) bg-green-100 text-green-600
                            @else bg-gray-100 text-gray-400 @endif">
                            @if($isTolak)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            @elseif($isSelesai)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </div>
                        @if(!$loop->last)
                            <div class="w-0.5 h-full @if($isSelesai) bg-green-300 @else bg-gray-200 @endif"></div>
                        @endif
                    </div>
                    <div class="pb-8 flex-1">
                        <p class="font-semibold text-gray-800">{{ $label }}</p>
                        @if($log)
                            <p class="text-sm text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</p>
                            @if($log->catatan)
                                <p class="text-sm text-gray-600 mt-1">{{ $log->catatan }}</p>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach

            @php $dataSiap = $permintaan->status === 'data_siap' || $permintaan->status === 'selesai'; @endphp
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                        @if($dataSiap) bg-green-100 text-green-600 @else bg-gray-100 text-gray-400 @endif">
                        @if($dataSiap)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6"/></svg>
                        @endif
                    </div>
                </div>
                <div class="pb-8 flex-1">
                    <p class="font-semibold text-gray-800">Data Siap Diunduh</p>
                    @if($dataSiap)
                        <p class="text-sm text-gray-500">{{ $permintaan->updated_at->format('d M Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if($permintaan->status === 'ditolak')
            @php $logTolak = $permintaan->approvalLog->where('keputusan', 'tolak')->last(); @endphp
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded ml-12">
                <p class="font-semibold text-red-700">Permintaan Ditolak</p>
                @if($logTolak && $logTolak->catatan)
                    <p class="text-sm text-red-600 mt-1">Alasan: {{ $logTolak->catatan }}</p>
                @else
                    <p class="text-sm text-red-600 mt-1">Alasan: {{ $permintaan->catatan_penolakan ?? 'Tidak disebutkan.' }}</p>
                @endif
            </div>
        @endif

        @if(in_array($permintaan->status, ['data_siap', 'selesai']) && $permintaan->file_hasil_path)
            <div class="mt-8 text-center">
                <a href="{{ route('permintaan.unduh', $permintaan) }}" class="inline-flex items-center gap-2 bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition shadow">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Unduh Data
                </a>
            </div>
        @endif
    </div>
@endsection