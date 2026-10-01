<section class="mt-6 rounded-xl border bg-white p-5 text-left" aria-labelledby="judul-kendala-dokumen">
    <h2 id="judul-kendala-dokumen" class="font-semibold text-gray-800">Kendala Dokumen</h2>
    @foreach($permintaan->kendala as $kendala)
        <article class="mt-3 rounded-lg border p-3 text-sm">
            <p class="font-medium">{{ $kendala->diselesaikan_at ? 'Sudah ditangani' : 'Menunggu tindak lanjut petugas' }} · {{ $kendala->created_at->format('d M Y H:i') }}</p>
            <p class="mt-2 whitespace-pre-line break-words">{{ $kendala->laporan }}</p>
            @if($kendala->tanggapan)
                <p class="mt-3 font-semibold">Tanggapan petugas</p>
                <p class="whitespace-pre-line break-words">{{ $kendala->tanggapan }}</p>
            @endif
        </article>
    @endforeach
    @if(!$permintaan->kendala->contains(fn ($item) => $item->diselesaikan_at === null))
        <details class="mt-3" @if($errors->has('laporan')) open @endif>
            <summary class="cursor-pointer font-medium text-primary-700">Laporkan kendala dokumen</summary>
            <form method="POST" action="{{ route('permintaan.kendala', $permintaan) }}" class="mt-3 space-y-3">
                @csrf
                <label for="laporan-kendala" class="block text-sm">Jelaskan kendala, misalnya file tidak bisa dibuka atau data belum sesuai.</label>
                <textarea id="laporan-kendala" name="laporan" rows="3" required maxlength="2000" class="w-full rounded-lg border p-3 text-sm">{{ old('laporan') }}</textarea>
                @error('laporan') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                <button class="rounded-lg bg-primary-600 px-4 py-2 font-semibold text-white">Kirim Laporan Kendala</button>
            </form>
        </details>
    @endif
</section>
