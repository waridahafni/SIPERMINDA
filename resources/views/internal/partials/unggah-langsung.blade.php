@if(\App\Services\UnggahDokumen::langsung())
    <input type="hidden" name="upload_token" value="">
    <span hidden data-unggah-langsung
        data-endpoint="{{ route('internal.dokumen.unggah') }}"
        data-tujuan="{{ $tujuan }}" data-target="{{ $target ?? '' }}"></span>
    <p data-status-unggah role="status" aria-live="polite" class="text-sm text-gray-600"></p>
    @error('upload_token') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    <noscript><p class="text-sm text-red-600">Aktifkan JavaScript untuk mengunggah file.</p></noscript>
    @once
        @push('scripts')
            <script src="{{ asset('js/unggah-dokumen.js') }}" defer></script>
        @endpush
    @endonce
@endif
