@extends('layouts.internal')

@section('title', 'Detail Permintaan - ' . $permintaan->nomor_tiket)

@section('content')
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('internal.permintaan.index') }}" class="text-sm text-primary-500 hover:underline">&larr; Kembali</a>
        <h1 class="text-2xl font-bold text-gray-800">Detail Permintaan</h1>
        @php
            $badge = match($permintaan->status) {
                'diajukan' => 'yellow',
                'diverifikasi_staf' => 'blue',
                'disetujui_kasi' => 'indigo',
                'disetujui_kabid' => 'purple',
                'ditolak' => 'red',
                'menunggu_upload' => 'orange',
                'data_siap' => 'green',
                'selesai' => 'teal',
                default => 'gray'
            };
        @endphp
        <span class="text-xs font-bold px-3 py-1 rounded-full bg-{{ $badge }}-100 text-{{ $badge }}-800">{{ str_replace('_', ' ', ucfirst($permintaan->status)) }}</span>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Informasi Permintaan</h2>
                <div class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500">No Tiket:</span> <span class="font-mono font-bold text-gray-800">{{ $permintaan->nomor_tiket }}</span></div>
                    <div><span class="text-gray-500">Tanggal:</span> <span class="text-gray-800">{{ $permintaan->created_at->format('d M Y H:i') }}</span></div>
                    <div><span class="text-gray-500">Jenis Data:</span> <span class="text-gray-800">{{ $permintaan->jenis_data }}</span></div>
                    <div><span class="text-gray-500">Periode:</span> <span class="text-gray-800">{{ $permintaan->periode_data }}</span></div>
                    <div><span class="text-gray-500">Kategori:</span> <span class="text-gray-800">{{ $permintaan->kategori->nama ?? '-' }}</span></div>
                </div>
                <div class="mt-4">
                    <span class="text-gray-500 text-sm">Tujuan Penggunaan:</span>
                    <p class="text-gray-800 mt-1">{{ $permintaan->tujuan_penggunaan }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Timeline Approval</h2>
                @php
                    $logTahap = $permintaan->approvalLog->keyBy('tahap');
                    $jumlahTahap = [
                        'staf' => $logTahap->get('staf'),
                        'kasi' => $logTahap->get('kasi'),
                        'kabid' => $logTahap->get('kabid'),
                    ];
                    $urutan = [
                        'staf' => 'Verifikasi Staf',
                        'kasi' => 'Persetujuan Kasi',
                        'kabid' => 'Persetujuan Kabid',
                        'upload' => 'Upload Data',
                    ];
                @endphp
                <div class="space-y-0">
                    @foreach($urutan as $key => $label)
                        @php
                            $log = $jumlahTahap[$key] ?? null;
                            $isSelesai = $log && $log->keputusan === 'setuju';
                        @endphp
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                                    @if($isSelesai) bg-green-100 text-green-600
                                    @elseif($key === 'upload' && $permintaan->file_hasil_path) bg-green-100 text-green-600
                                    @else bg-gray-100 text-gray-400 @endif">
                                    @if($isSelesai) <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @elseif($key === 'upload' && $permintaan->file_hasil_path) <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3"/></svg>
                                    @else {{ $loop->iteration }} @endif
                                </div>
                                @if(!$loop->last)
                                    <div class="w-0.5 h-full @if($isSelesai || ($key === 'kabid' && $permintaan->file_hasil_path)) bg-green-300 @else bg-gray-200 @endif"></div>
                                @endif
                            </div>
                            <div class="pb-6 flex-1">
                                <p class="font-medium text-gray-800">{{ $label }}</p>
                                @if($log)
                                    <p class="text-xs text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</p>
                                    @if($log->catatan)
                                        <p class="text-xs text-gray-600 mt-1">{{ $log->catatan }}</p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($permintaan->status === 'ditolak')
                @php $logTolak = $permintaan->approvalLog->where('keputusan', 'tolak')->last(); @endphp
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <p class="font-semibold text-red-700">Permintaan Ditolak</p>
                    @if($logTolak && $logTolak->catatan)
                        <p class="text-sm text-red-600 mt-1">Alasan: {{ $logTolak->catatan }}</p>
                    @else
                        <p class="text-sm text-red-600 mt-1">Alasan: {{ $permintaan->catatan_penolakan ?? 'Tidak disebutkan.' }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Informasi Pemohon</h2>
                <div class="text-sm space-y-2">
                    <p><span class="text-gray-500">Nama:</span> {{ $permintaan->pemohon->nama ?? '-' }}</p>
                    <p><span class="text-gray-500">No HP:</span> {{ $permintaan->pemohon->no_hp ?? '-' }}</p>
                    <p><span class="text-gray-500">Email:</span> {{ $permintaan->pemohon->email ?? '-' }}</p>
                    @if($permintaan->pemohon && $permintaan->pemohon->jenis_pemohon === 'instansi')
                        <p><span class="text-gray-500">Instansi:</span> {{ $permintaan->pemohon->nama_instansi ?? '-' }}</p>
                    @endif
                </div>
            </div>

            @php
                $user = Auth::user();
                $tahap = match($permintaan->status) {
                    'diajukan' => 'staf',
                    'diverifikasi_staf' => 'kasi',
                    'disetujui_kasi' => 'kabid',
                    'menunggu_upload' => 'upload',
                    default => null,
                };
                $bisaTindak = $tahap && !in_array($tahap, ['upload'])
                    && match($tahap) {
                        'staf' => $user->can('verifikasi-permintaan'),
                        'kasi' => $user->can('approve-level-1'),
                        'kabid' => $user->can('approve-level-2'),
                        default => false,
                    };
                $labelTahap = match($tahap) {
                    'staf' => 'Verifikasi Permintaan',
                    'kasi' => 'Persetujuan Kasi',
                    'kabid' => 'Persetujuan Kabid',
                    'upload' => 'Upload Data',
                    default => '',
                };
            @endphp

            @if($bisaTindak)
                <div class="bg-white rounded-xl shadow-sm border p-6">
                    <h2 class="font-semibold text-gray-800 mb-4">{{ $labelTahap }}</h2>
                    <form method="POST" action="{{ route('internal.permintaan.keputusan', $permintaan) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea name="catatan" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm" placeholder="Catatan (wajib jika menolak)"></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" name="keputusan" value="setuju" class="flex-1 bg-green-600 text-white py-2 rounded-lg font-semibold hover:bg-green-700 transition text-sm">Setujui</button>
                            <button type="submit" name="keputusan" value="tolak" class="flex-1 bg-red-500 text-white py-2 rounded-lg font-semibold hover:bg-red-600 transition text-sm">Tolak</button>
                        </div>
                    </form>
                </div>
            @endif

            @if($tahap === 'upload' && $user->can('upload-hasil'))
                <div class="bg-white rounded-xl shadow-sm border p-6">
                    <h2 class="font-semibold text-gray-800 mb-4">Upload File Data</h2>
                    <form method="POST" action="{{ route('internal.permintaan.upload', $permintaan) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">File Data (PDF/Excel/ZIP/CSV)</label>
                            <input type="file" name="file_hasil" required accept=".pdf,.xlsx,.xls,.csv,.zip"
                                class="w-full text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:bg-primary-50 file:text-primary-700 file:font-medium">
                            @error('file_hasil') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg font-semibold hover:bg-green-700 transition text-sm">Upload & Selesaikan</button>
                    </form>
                </div>
            @endif

            @if($permintaan->file_hasil_path)
                <div class="bg-white rounded-xl shadow-sm border p-6">
                    <h2 class="font-semibold text-gray-800 mb-2">File Data</h2>
                    <a href="{{ route('internal.permintaan.unduh', $permintaan) }}" class="inline-flex items-center gap-2 text-primary-500 hover:underline text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Unduh File Data
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection