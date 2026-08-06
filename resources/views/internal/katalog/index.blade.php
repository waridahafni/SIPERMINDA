@extends('layouts.internal')

@section('title', 'Katalog Data')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Katalog Data</h1>
            <p class="text-gray-500 text-sm">Kelola dataset statistik yang tersedia.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('internal.katalog.create') }}" class="bg-primary-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-primary-600 transition shadow">Tambah Dataset</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium">Judul</th>
                        <th class="text-left px-6 py-3 font-medium">Kategori</th>
                        <th class="text-left px-6 py-3 font-medium">Periode</th>
                        <th class="text-left px-6 py-3 font-medium">Status</th>
                        <th class="text-center px-6 py-3 font-medium">Versi</th>
                        <th class="text-left px-6 py-3 font-medium">Uploader</th>
                        <th class="text-center px-6 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($datasets as $ds)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $ds->judul }}</td>
                            <td class="px-6 py-3"><span class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full">{{ $ds->kategori->nama ?? '-' }}</span></td>
                            <td class="px-6 py-3 text-gray-500">{{ $ds->periode }}</td>
                            <td class="px-6 py-3">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full @if($ds->status === 'aktif') bg-green-100 text-green-700 @else bg-gray-100 text-gray-700 @endif">{{ ucfirst($ds->status) }}</span>
                            </td>
                            <td class="px-6 py-3 text-center text-gray-500">{{ $ds->versi ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $ds->uploader->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-center">
                                <div class="flex items-center justify-center gap-2 text-xs">
                                    <a href="{{ route('internal.katalog.edit', $ds) }}" class="text-primary-500 hover:underline font-medium">Edit</a>
                                    <a href="{{ route('internal.katalog.edit', $ds) }}?revisi=1" class="text-secondary-500 hover:underline font-medium">Revisi</a>
                                    <a href="{{ route('katalog.detail', $ds) }}" class="text-gray-500 hover:underline font-medium">Lihat</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">Belum ada dataset</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($datasets->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $datasets->links() }}
            </div>
        @endif
    </div>
@endsection