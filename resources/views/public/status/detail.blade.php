@extends('layouts.public')

@section('title', 'Status Permintaan - BPS Kabupaten Padang Lawas')

@section('content')
    @php
        $labelStatus = match($permintaan->status) {
            'diajukan' => 'Diajukan',
            'disetujui_petugas' => 'Disetujui Petugas',
            'menunggu_info_pemohon' => 'Menunggu Info Pemohon',
            'ditolak' => 'Ditolak',
            'data_siap' => 'Data Siap',
            'selesai' => 'Selesai',
            default => str_replace('_', ' ', ucfirst($permintaan->status)),
        };
        $pemohonAktifId = session('pemohon_id');
        $akunPemilikAktif = $pemohonAktifId !== null
            && (string) $pemohonAktifId === (string) $permintaan->pemohon_id;
        $klarifikasiAktif = $permintaan->klarifikasi->first(
            fn ($klarifikasi) => $klarifikasi->dijawab_at === null
        );
    @endphp
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <a href="{{ route('cek-status') }}" class="text-sm text-primary-500 hover:underline">&larr; Cek Status Lainnya</a>
            <div class="mt-4 flex items-center gap-4 flex-wrap">
                <h1 class="text-2xl font-bold text-gray-800">Status Permintaan</h1>
                <span role="status" aria-label="Status permintaan: {{ $labelStatus }}" class="text-xs font-bold px-3 py-1 rounded-full
                    @switch($permintaan->status)
                        @case('diajukan') bg-yellow-100 text-yellow-800 @break
                        @case('disetujui_petugas') bg-blue-100 text-blue-800 @break
                        @case('menunggu_info_pemohon') bg-amber-100 text-amber-800 @break
                        @case('ditolak') bg-red-100 text-red-800 @break
                        @case('data_siap') bg-green-100 text-green-800 @break
                        @case('selesai') bg-teal-100 text-teal-800 @break
                        @default bg-gray-100 text-gray-800
                    @endswitch">
                    {{ $labelStatus }}
                </span>
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-8">
        @if($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700" role="alert" aria-labelledby="error-detail-status">
                <p id="error-detail-status" class="font-semibold">Jawaban belum dapat diproses:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border p-6 mb-8">
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Nomor Tiket:</span> <span class="font-mono font-bold text-gray-800">{{ $permintaan->nomor_tiket }}</span></div>
                <div><span class="text-gray-500">Tanggal Diajukan:</span> <span class="text-gray-800">{{ $permintaan->created_at->format('d M Y H:i') }}</span></div>
                <div><span class="text-gray-500">Jenis Data:</span> <span class="text-gray-800">{{ $permintaan->jenis_data }}</span></div>
                <div><span class="text-gray-500">Periode:</span> <span class="text-gray-800">{{ $permintaan->periode_data }}</span></div>
            </div>
        </div>

        @php
            $labelKeputusan = [
                'setuju' => 'Permintaan disetujui petugas',
                'tolak' => 'Permintaan ditolak',
                'data_siap' => 'Data siap diunduh',
                'selesai' => 'Permintaan selesai',
            ];
            $timeline = collect([[
                'waktu' => $permintaan->created_at,
                'judul' => 'Permintaan diajukan',
                'keterangan' => 'Nomor tiket '.$permintaan->nomor_tiket.' berhasil dibuat.',
                'warna' => 'bg-primary-500',
            ]])
                ->merge($permintaan->approvalLog->map(fn ($log) => [
                    'waktu' => $log->created_at,
                    'judul' => $labelKeputusan[$log->keputusan] ?? 'Status permintaan diperbarui',
                    'keterangan' => null,
                    'warna' => $log->keputusan === 'tolak' ? 'bg-red-500' : 'bg-green-600',
                ]))
                ->merge($permintaan->klarifikasi->flatMap(function ($klarifikasi) {
                    $peristiwa = [[
                        'waktu' => $klarifikasi->created_at,
                        'judul' => 'Petugas meminta informasi tambahan',
                        'keterangan' => null,
                        'warna' => 'bg-amber-500',
                    ]];

                    if ($klarifikasi->dijawab_at) {
                        $peristiwa[] = [
                            'waktu' => $klarifikasi->dijawab_at,
                            'judul' => 'Informasi tambahan dijawab',
                            'keterangan' => null,
                            'warna' => 'bg-primary-500',
                        ];
                    }

                    return $peristiwa;
                }))
                ->sortBy('waktu')
                ->values();
        @endphp

        <section class="mb-10" aria-labelledby="timeline-status">
            <h2 id="timeline-status" class="text-lg font-semibold text-gray-800 mb-4">Timeline Status</h2>
            <ol class="space-y-0">
                @foreach($timeline as $peristiwa)
                    <li class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <span class="mt-1 h-3.5 w-3.5 rounded-full {{ $peristiwa['warna'] }} ring-4 ring-white shadow"></span>
                            @if(! $loop->last)<span class="my-1 w-0.5 flex-1 bg-gray-200"></span>@endif
                        </div>
                        <div class="pb-6">
                            <p class="font-semibold text-gray-800">{{ $peristiwa['judul'] }}</p>
                            <time class="block text-sm text-gray-500">{{ $peristiwa['waktu']->format('d M Y H:i') }}</time>
                            @if($peristiwa['keterangan'])<p class="mt-1 text-sm text-gray-600">{{ $peristiwa['keterangan'] }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <h2 class="text-lg font-semibold text-gray-800 mb-4">Detail Verifikasi</h2>

        @php
            $logTahap = $permintaan->approvalLog->keyBy('tahap');
            $urutan = [
                'staf' => 'Verifikasi Staf',
                'kasi' => 'Persetujuan Kasi',
                'kabid' => 'Persetujuan Kabid',
            ];
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
                                <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            @elseif($isSelesai)
                                <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
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
                        <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }}</p>
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
                            <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3"/></svg>
                        @else
                            <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6"/></svg>
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

        @if($permintaan->klarifikasi->isNotEmpty() && $akunPemilikAktif)
            <section class="mt-8" aria-labelledby="riwayat-info-tambahan">
                <h2 id="riwayat-info-tambahan" class="text-lg font-semibold text-gray-800 mb-4">Informasi Tambahan</h2>
                <div class="space-y-4">
                    @foreach($permintaan->klarifikasi as $klarifikasi)
                        <article class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold text-amber-900">Pertanyaan petugas</p>
                                <time class="text-xs text-amber-700" datetime="{{ $klarifikasi->created_at->toIso8601String() }}">
                                    {{ $klarifikasi->created_at->format('d M Y H:i') }}
                                </time>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-800">{{ $klarifikasi->pertanyaan }}</p>

                            @if($klarifikasi->dijawab_at)
                                <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-semibold text-green-800">Jawaban Anda</p>
                                        <time class="text-xs text-green-700" datetime="{{ $klarifikasi->dijawab_at->toIso8601String() }}">
                                            {{ $klarifikasi->dijawab_at->format('d M Y H:i') }}
                                        </time>
                                    </div>
                                    <p class="mt-2 whitespace-pre-line text-sm text-gray-800">{{ $klarifikasi->jawaban }}</p>
                                </div>
                            @else
                                <p class="mt-3 text-sm font-medium text-amber-800">Pertanyaan ini belum dijawab.</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if($permintaan->status === 'menunggu_info_pemohon' && $klarifikasiAktif)
            <section class="mt-6 rounded-xl border border-amber-300 bg-white p-6 shadow-sm" aria-labelledby="jawab-info-tambahan">
                <h2 id="jawab-info-tambahan" class="text-lg font-semibold text-gray-800">Jawab Informasi Tambahan</h2>

                @if($akunPemilikAktif)
                    <p class="mt-1 text-sm text-gray-600">Berikan jawaban sejelas mungkin agar petugas dapat melanjutkan proses permintaan.</p>
                    <form method="POST" action="{{ route('pemohon.permintaan.info-tambahan.jawab', $permintaan) }}" class="mt-4 space-y-4"
                        x-data="{ submitting: false }"
                        @submit="if (submitting) { $event.preventDefault() } else { submitting = true }">
                        @csrf
                        <div>
                            <label for="jawaban-info-tambahan" class="block text-sm font-medium text-gray-700">Jawaban Anda</label>
                            <textarea id="jawaban-info-tambahan" name="jawaban" rows="5" required maxlength="5000"
                                @error('jawaban') aria-invalid="true" aria-describedby="jawaban-info-error" @enderror
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500">{{ old('jawaban') }}</textarea>
                            @error('jawaban') <p id="jawaban-info-error" class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" :aria-disabled="submitting.toString()"
                            :class="submitting ? 'pointer-events-none cursor-wait opacity-60' : ''"
                            class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-5 py-3 font-semibold text-white transition hover:bg-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">
                            <span x-show="!submitting">Kirim Jawaban</span>
                            <span x-show="submitting" x-cloak class="js-only">Memproses...</span>
                        </button>
                    </form>
                @elseif($pemohonAktifId === null)
                    <p class="mt-2 text-sm text-gray-600">Masuk dengan akun pemohon yang mengajukan tiket ini untuk mengirim jawaban.</p>
                    <a href="{{ route('pemohon.permintaan.show', $permintaan) }}"
                        class="mt-4 inline-flex rounded-lg bg-primary-500 px-5 py-3 font-semibold text-white hover:bg-primary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
                        Masuk untuk Menjawab
                    </a>
                @else
                    <div class="mt-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800" role="alert">
                        Anda sedang masuk dengan akun yang berbeda. Jawaban hanya dapat dikirim oleh pemilik permintaan.
                    </div>
                @endif
            </section>
        @endif

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
                @if($akunPemilikAktif)
                    <p class="text-sm text-gray-600 mb-3">File hasil tersedia untuk akun pemohon Anda.</p>
                    <a href="{{ route('permintaan.unduh', $permintaan) }}" class="inline-flex items-center gap-2 bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-600 focus-visible:ring-offset-2 transition shadow">
                        <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Unduh Data
                    </a>
                @elseif($pemohonAktifId === null)
                    <p class="text-sm text-gray-600 mb-3">Data sudah siap. Masuk dengan akun pemohon yang mengajukan permintaan ini untuk mengunduhnya.</p>
                    {{-- Tautan protected membuat middleware menyimpan URL unduhan sebagai tujuan setelah login. --}}
                    <a href="{{ route('permintaan.unduh', $permintaan) }}" class="inline-flex items-center gap-2 bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-600 focus-visible:ring-offset-2 transition shadow">
                        <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Masuk untuk Mengunduh
                    </a>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4 text-sm" role="alert">
                        Anda sedang masuk dengan akun pemohon yang berbeda. File hanya dapat diunduh oleh akun yang mengajukan permintaan ini.
                    </div>
                    <a href="{{ route('pemohon.permintaan.index') }}" class="inline-block mt-3 text-sm font-semibold text-primary-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded">Lihat Permintaan Saya</a>
                @endif
            </div>
        @endif
    </div>
@endsection
