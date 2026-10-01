@if(in_array($permintaan->status, \App\Models\PermintaanData::STATUS_AKTIF, true))
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
        <h2 class="font-semibold">Batas Layanan</h2>
        <p class="mt-2 text-sm">{{ $permintaan->batasLayanan()->format('d M Y H:i') }} ({{ config('layanan.sla_hari_kerja') }} hari kerja)</p>
        <p class="mt-1 text-xs text-gray-600">Sabtu, Minggu, dan libur nasional tidak dihitung. Cuti bersama tetap dihitung.</p>
        @if($permintaan->batasLayanan()->lte(now())) <p class="mt-2 font-semibold text-red-700">Melewati batas layanan. Segera tindak lanjuti.</p> @endif
    </div>
@endif
@if($permintaan->file_hasil_path)
    <div class="rounded-xl border bg-white p-5">
        <h2 class="font-semibold">Akses Dokumen Pemohon</h2>
        @if($permintaan->unduhan_log_count)
            <p class="mt-2 text-sm">Unduhan diminta {{ $permintaan->unduhan_log_count }} kali.</p>
            <p class="mt-1 text-sm">Terakhir: {{ \Carbon\Carbon::parse($permintaan->unduhan_log_max_downloaded_at)->format('d M Y H:i') }}</p>
            <p class="mt-2 text-xs text-gray-500">Catatan ini menunjukkan permintaan unduhan, bukan konfirmasi file selesai diterima.</p>
        @else
            <p class="mt-2 text-sm text-amber-800">Pemohon belum mengunduh dokumen melalui aplikasi.</p>
        @endif
    </div>
@endif
@if($permintaan->feedback)
    <div class="rounded-xl border bg-white p-5">
        <h2 class="font-semibold">Survei Pemohon: {{ $permintaan->feedback->rating }}/5</h2>
        <p class="mt-2 whitespace-pre-line break-words text-sm">{{ $permintaan->feedback->komentar ?: 'Tanpa komentar.' }}</p>
    </div>
@endif
@foreach($permintaan->kendala as $kendala)
    <div class="rounded-xl border bg-white p-5">
        <h2 class="font-semibold">Kendala Dokumen — {{ $kendala->diselesaikan_at ? 'Sudah ditangani' : 'Perlu tindak lanjut' }}</h2>
        <p class="mt-1 text-xs text-gray-500">{{ $kendala->created_at->format('d M Y H:i') }}</p>
        <p class="mt-2 whitespace-pre-line break-words text-sm">{{ $kendala->laporan }}</p>
        @if($kendala->diselesaikan_at)
            <p class="mt-3 text-sm font-semibold">Tanggapan petugas</p>
            <p class="whitespace-pre-line break-words text-sm">{{ $kendala->tanggapan }}</p>
        @else
            @can('upload-hasil')
                <form method="POST" action="{{ route('internal.permintaan.kendala.selesai', [$permintaan, $kendala]) }}" class="mt-4 space-y-3">
                    @csrf
                    <label for="tanggapan-{{ $kendala->id }}" class="block text-sm">Tanggapan untuk pemohon setelah kendala ditangani</label>
                    <textarea id="tanggapan-{{ $kendala->id }}" name="tanggapan" required maxlength="2000" rows="3" class="w-full rounded-lg border p-2 text-sm">{{ old('tanggapan') }}</textarea>
                    <button class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white">Tandai Kendala Selesai</button>
                </form>
            @endcan
        @endif
    </div>
@endforeach
