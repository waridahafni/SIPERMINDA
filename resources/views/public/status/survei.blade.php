@if($permintaan->feedback)
    <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-5 text-left" role="status">
        <p class="font-semibold text-green-800">Terima kasih sudah mengisi survei layanan.</p>
        <p class="mt-1 text-sm text-green-700">Penilaian Anda: {{ $permintaan->feedback->rating }} dari 5.</p>
    </div>
@else
    <section class="mb-6 rounded-xl border border-primary-200 bg-primary-50 p-5 text-left" aria-labelledby="judul-survei">
        <h2 id="judul-survei" class="text-lg font-semibold text-gray-800">Bantu kami meningkatkan layanan</h2>
        <p class="mt-2 text-sm text-gray-700">Setelah menerima dokumen, kami menyarankan Anda mengisi survei kepuasan berikut. Survei ini opsional dan dokumen tetap dapat diunduh kapan saja.</p>
        <form method="POST" action="{{ route('permintaan.feedback', $permintaan) }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="survei-rating" class="block text-sm font-medium text-gray-700">Seberapa puas Anda dengan layanan kami?</label>
                <select id="survei-rating" name="rating" required
                    @error('rating') aria-invalid="true" aria-describedby="survei-rating-error" @enderror
                    class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Pilih penilaian</option>
                    @foreach([1 => 'Sangat tidak puas', 2 => 'Tidak puas', 3 => 'Cukup puas', 4 => 'Puas', 5 => 'Sangat puas'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected((string) old('rating') === (string) $nilai)>{{ $nilai }} — {{ $label }}</option>
                    @endforeach
                </select>
                @error('rating') <p id="survei-rating-error" class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="survei-komentar" class="block text-sm font-medium text-gray-700">Saran atau komentar (opsional)</label>
                <textarea id="survei-komentar" name="komentar" rows="3" maxlength="2000"
                    @error('komentar') aria-invalid="true" aria-describedby="survei-komentar-error" @enderror
                    class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">{{ old('komentar') }}</textarea>
                @error('komentar') <p id="survei-komentar-error" class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2 font-semibold text-white hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">Kirim Survei</button>
        </form>
    </section>
@endif
