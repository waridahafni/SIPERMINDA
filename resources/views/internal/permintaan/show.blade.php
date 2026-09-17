@extends('layouts.internal')

@section('title', 'Detail Permintaan - ' . $permintaan->nomor_tiket)

@section('content')
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('internal.permintaan.index') }}" class="text-sm text-primary-500 hover:underline">&larr; Kembali</a>
        <h1 class="text-2xl font-bold text-gray-800">Detail Permintaan</h1>
        @php
            $badge = match($permintaan->status) {
                'diajukan' => 'yellow',
                'disetujui_petugas' => 'blue',
                'menunggu_info_pemohon' => 'amber',
                'ditolak' => 'red',
                'data_siap' => 'green',
                'selesai' => 'teal',
                default => 'gray'
            };
        @endphp
        <span class="text-xs font-bold px-3 py-1 rounded-full bg-{{ $badge }}-100 text-{{ $badge }}-800">
            {{ $permintaan->status === 'menunggu_info_pemohon' ? 'Menunggu Info Pemohon' : str_replace('_', ' ', ucfirst($permintaan->status)) }}
        </span>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700" role="alert" aria-labelledby="error-detail-permintaan">
            <p id="error-detail-permintaan" class="font-semibold">Tindakan belum dapat diproses:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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

            @if($permintaan->klarifikasi->isNotEmpty())
                <div class="bg-white rounded-xl shadow-sm border p-6">
                    <h2 class="font-semibold text-gray-800 mb-4">Riwayat Informasi Tambahan</h2>
                    <div class="space-y-4">
                        @foreach($permintaan->klarifikasi as $klarifikasi)
                            <article class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-amber-900">
                                        Pertanyaan tahap {{ ucfirst($klarifikasi->tahap) }}
                                    </p>
                                    <time class="text-xs text-amber-700" datetime="{{ $klarifikasi->created_at->toIso8601String() }}">
                                        {{ $klarifikasi->created_at->format('d M Y H:i') }}
                                    </time>
                                </div>
                                <p class="mt-2 whitespace-pre-line text-sm text-gray-800">{{ $klarifikasi->pertanyaan }}</p>
                                <p class="mt-2 text-xs text-gray-500">Diminta oleh {{ $klarifikasi->peminta->name ?? 'Petugas' }}</p>

                                @if($klarifikasi->catatan_internal)
                                    <div class="mt-3 rounded-md border border-gray-200 bg-white p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Catatan internal</p>
                                        <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $klarifikasi->catatan_internal }}</p>
                                    </div>
                                @endif

                                @if($klarifikasi->dijawab_at)
                                    <div class="mt-3 rounded-md border border-green-200 bg-green-50 p-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-semibold text-green-800">Jawaban pemohon</p>
                                            <time class="text-xs text-green-700" datetime="{{ $klarifikasi->dijawab_at->toIso8601String() }}">
                                                {{ $klarifikasi->dijawab_at->format('d M Y H:i') }}
                                            </time>
                                        </div>
                                        <p class="mt-1 whitespace-pre-line text-sm text-gray-800">{{ $klarifikasi->jawaban }}</p>
                                    </div>
                                @else
                                    <p class="mt-3 text-sm font-medium text-amber-800">Menunggu jawaban pemohon.</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

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
                        'staf' => 'Persetujuan Petugas',
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
                                    <p class="text-xs text-gray-500">{{ $log->created_at?->format('d M Y H:i') ?? 'Waktu tidak tersedia' }}</p>
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
                    'disetujui_petugas' => 'upload',
                    default => null,
                };
                $bisaTindak = $tahap && !in_array($tahap, ['upload'])
                    && match($tahap) {
                        'staf' => $user->can('verifikasi-permintaan'),
                        default => false,
                    };
                $labelTahap = match($tahap) {
                    'staf' => 'Verifikasi Permintaan',
                    'upload' => 'Upload Data',
                    default => '',
                };
            @endphp

            @if($bisaTindak)
                <div class="bg-white rounded-xl shadow-sm border p-6">
                    <h2 class="font-semibold text-gray-800 mb-4">{{ $labelTahap }}</h2>
                    <form method="POST" action="{{ route('internal.permintaan.keputusan', $permintaan) }}" class="space-y-4"
                        data-nomor-tiket="{{ $permintaan->nomor_tiket }}"
                        onsubmit="if (this.dataset.submitting === 'true') return false; const keputusan = event.submitter?.value ?? ''; const aksi = keputusan === 'tolak' ? 'menolak' : (keputusan === 'minta_info' ? 'meminta informasi tambahan untuk' : 'menyetujui'); if (!window.confirm('Anda yakin ingin ' + aksi + ' permintaan ' + this.dataset.nomorTiket + '?')) return false; this.dataset.submitting = 'true'; this.querySelectorAll('[data-tombol-keputusan]').forEach((tombol) => { tombol.style.opacity = '0.5'; tombol.style.pointerEvents = 'none'; tombol.setAttribute('aria-disabled', 'true'); }); this.querySelector('[data-status-proses]').hidden = false; return true;">
                        @csrf
                        @error('keputusan')
                            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">{{ $message }}</div>
                        @enderror
                        <noscript>
                            <p class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900" role="alert">Konfirmasi otomatis tidak tersedia karena JavaScript dimatikan. Periksa pilihan Anda dengan teliti sebelum menekan tombol.</p>
                        </noscript>
                        <div>
                            <label for="catatan-keputusan" class="block text-sm font-medium text-gray-700 mb-1">Catatan atau pertanyaan untuk pemohon</label>
                            <textarea id="catatan-keputusan" name="catatan" rows="3" aria-describedby="catatan-keputusan-bantuan"
                                @error('catatan') aria-invalid="true" @enderror
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
                                placeholder="Wajib jika menolak atau meminta informasi tambahan">{{ old('catatan') }}</textarea>
                            <p id="catatan-keputusan-bantuan" class="mt-1 text-xs text-gray-500">Semua isi kolom ini dapat dilihat pemohon dan dapat dikirim melalui email. Wajib jika menolak atau meminta informasi tambahan.</p>
                            @error('catatan') <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="catatan-internal" class="block text-sm font-medium text-gray-700 mb-1">Catatan internal <span class="font-normal text-gray-500">(opsional)</span></label>
                            <textarea id="catatan-internal" name="catatan_internal" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
                                placeholder="Hanya disimpan saat meminta info dan tidak ditampilkan kepada pemohon">{{ old('catatan_internal') }}</textarea>
                            @error('catatan_internal') <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <button type="submit" name="keputusan" value="setuju"
                                data-tombol-keputusan
                                class="flex-1 bg-green-600 text-white py-2 rounded-lg font-semibold hover:bg-green-700 transition text-sm">Setujui</button>
                            <button type="submit" name="keputusan" value="tolak"
                                data-tombol-keputusan
                                class="flex-1 bg-red-500 text-white py-2 rounded-lg font-semibold hover:bg-red-600 transition text-sm">Tolak</button>
                            <button type="submit" name="keputusan" value="minta_info"
                                data-tombol-keputusan
                                class="flex-1 bg-amber-500 text-white py-2 rounded-lg font-semibold hover:bg-amber-600 transition text-sm">Minta Info</button>
                        </div>
                        <p hidden data-status-proses role="status" aria-live="polite" class="text-center text-xs text-gray-500">Memproses keputusan...</p>
                    </form>
                </div>
            @endif

            @if($permintaan->status === 'menunggu_info_pemohon')
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-6" role="status">
                    <h2 class="font-semibold text-amber-900">Menunggu Info Pemohon</h2>
                    <p class="mt-2 text-sm text-amber-800">Tindakan approval berikutnya tersedia kembali setelah pemohon menjawab pertanyaan.</p>
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

            @if($permintaan->status === 'data_siap' && $user->can('upload-hasil'))
                <div class="bg-white rounded-xl shadow-sm border p-6">
                    <h2 class="font-semibold text-gray-800 mb-2">Penutupan Permintaan</h2>
                    <p class="text-sm text-gray-500 mb-3">Tandai permintaan sebagai selesai setelah pemohon menerima data.</p>
                    <form method="POST" action="{{ route('internal.permintaan.selesai', $permintaan) }}">
                        @csrf
                        <button type="submit" class="w-full bg-teal-600 text-white py-2 rounded-lg font-semibold hover:bg-teal-700 transition text-sm">Tandai Selesai</button>
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
