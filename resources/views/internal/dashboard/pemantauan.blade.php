    <div class="mb-6 rounded-xl border bg-white p-6">
        <h2 class="text-lg font-semibold">Kepuasan Pemohon</h2>
        <p class="mt-2">{{ $jumlahSurvei }} respons · Rata-rata <strong>{{ $rataRataSurvei === null ? '—' : number_format($rataRataSurvei, 2, ',', '.') }}/5</strong></p>
        @can('lihat-permintaan')
            <div class="mt-3 flex flex-wrap gap-4">
                <a href="{{ route('internal.survei.index') }}" class="text-primary-700 underline">Lihat hasil dan komentar</a>
                <a href="{{ route('internal.survei.export') }}" class="text-primary-700 underline">Unduh Hasil Survei (CSV)</a>
            </div>
        @endcan
    </div>
    <section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-6" aria-labelledby="judul-pengingat">
        <h2 id="judul-pengingat" class="text-lg font-semibold text-amber-900">Pengingat Batas Layanan — {{ $permintaanMelewatiSla->total() }} permintaan</h2>
        <p class="mt-2 text-sm text-amber-900">Target {{ $slaHariKerja }} hari kerja sejak pengajuan pada jam yang sama, di luar Sabtu, Minggu, dan libur nasional. Cuti bersama dan waktu menunggu jawaban pemohon tetap dihitung.</p>
        <p class="mt-1 text-xs text-amber-900">Kalender libur nasional tersedia untuk tahun {{ implode(', ', array_keys(config('hari_libur', []))) }}.</p>
        <ul class="mt-4 space-y-3">
            @forelse($permintaanMelewatiSla as $item)
                <li>
                    @can('lihat-permintaan')<a class="font-semibold text-primary-700 underline" href="{{ route('internal.permintaan.show', $item) }}">{{ $item->nomor_tiket }}</a>@else {{ $item->nomor_tiket }} @endcan
                    <span class="text-sm">— {{ $item->pemohon?->nama }} · Batas {{ $item->batasLayanan()->format('d M Y H:i') }} · {{ str_replace('_', ' ', $item->status) }}</span>
                </li>
            @empty
                <li class="text-sm">Tidak ada permintaan yang melewati batas layanan.</li>
            @endforelse
        </ul>
        <div class="mt-4">{{ $permintaanMelewatiSla->withQueryString()->links() }}</div>
    </section>
    @can('lihat-permintaan')
        <section class="mb-6 rounded-xl border bg-white p-6" aria-labelledby="judul-kendala">
            <h2 id="judul-kendala" class="text-lg font-semibold">Kendala Dokumen — {{ $kendalaTerbuka->total() }} belum ditangani</h2>
            @forelse($kendalaTerbuka as $item)
                <div class="mt-4 border-t pt-3">
                    <a href="{{ route('internal.permintaan.show', $item->permintaan) }}" class="font-semibold text-primary-700 underline">{{ $item->permintaan->nomor_tiket }}</a>
                    <p class="mt-1 whitespace-pre-line break-words text-sm">{{ $item->laporan }}</p>
                    <p class="mt-1 text-xs text-gray-500">Dilaporkan {{ $item->created_at->format('d M Y H:i') }}</p>
                </div>
            @empty
                <p class="mt-3 text-sm text-gray-500">Tidak ada kendala yang menunggu tindak lanjut.</p>
            @endforelse
            <div class="mt-4">{{ $kendalaTerbuka->withQueryString()->links() }}</div>
        </section>
    @endcan
