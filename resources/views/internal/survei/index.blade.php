@extends('layouts.internal')
@section('title', 'Hasil Survei')
@section('content')
    <h1 class="text-2xl font-bold text-gray-800">Hasil Survei Kepuasan</h1>
    <p class="mt-2 text-gray-600">{{ $jumlah }} respons · Rata-rata {{ $rataRata === null ? '—' : number_format($rataRata, 2, ',', '.') }} dari 5</p>
    <form method="GET" class="my-6 flex flex-wrap items-end gap-4">
        <div><label for="tanggal-mulai" class="block text-sm font-medium">Tanggal mulai</label><input id="tanggal-mulai" type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="mt-1 rounded border p-2"></div>
        <div><label for="tanggal-selesai" class="block text-sm font-medium">Tanggal selesai</label><input id="tanggal-selesai" type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" class="mt-1 rounded border p-2"></div>
        <button class="rounded-lg bg-primary-600 px-4 py-2 text-white">Terapkan Filter</button>
        <a href="{{ route('internal.survei.index') }}" class="px-2 py-2 text-primary-700 underline">Reset</a>
        <a href="{{ route('internal.survei.export', request()->only('tanggal_mulai', 'tanggal_selesai')) }}" class="rounded-lg bg-green-700 px-4 py-2 text-white">Unduh Hasil Survei (CSV)</a>
    </form>
    @if($errors->any()) <p class="mb-4 text-red-700" role="alert">{{ $errors->first() }}</p> @endif
    <p class="mb-3 text-sm text-gray-500">Filter dan unduhan mengikuti tanggal pengisian survei. File CSV dapat dibuka di Excel.</p>
    <div class="overflow-x-auto rounded-xl border bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50"><tr><th class="p-4">Tanggal</th><th class="p-4">Tiket</th><th class="p-4">Pemohon</th><th class="p-4">Rating</th><th class="p-4">Komentar</th></tr></thead>
            <tbody class="divide-y">
                @forelse($survei as $item)
                    <tr>
                        <td class="p-4">{{ $item->created_at->format('d M Y H:i') }}</td>
                        <td class="p-4">@if($item->permintaan)<a href="{{ route('internal.permintaan.show', $item->permintaan) }}" class="text-primary-700 underline">{{ $item->permintaan->nomor_tiket }}</a>@else — @endif</td>
                        <td class="p-4">{{ $item->pemohon?->nama ?? '—' }}</td>
                        <td class="p-4">{{ $item->rating }}/5</td>
                        <td class="p-4 whitespace-pre-line break-words">{{ $item->komentar ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-gray-500">Belum ada respons survei pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $survei->links() }}</div>
@endsection
