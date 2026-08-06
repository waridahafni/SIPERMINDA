@extends('layouts.internal')

@section('title', 'Manajemen Pengguna')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Pengguna</h1>
            <p class="text-gray-500 text-sm">Kelola akun staf, kasi, kabid, dan admin.</p>
        </div>
        <a href="{{ route('internal.pengguna.create') }}" class="bg-primary-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-primary-600 transition shadow">Tambah Pengguna</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium">Nama</th>
                        <th class="text-left px-6 py-3 font-medium">Email</th>
                        <th class="text-left px-6 py-3 font-medium">Role</th>
                        <th class="text-left px-6 py-3 font-medium">Status</th>
                        <th class="text-center px-6 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $user->name }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $user->email }}</td>
                            <td class="px-6 py-3">
                                @php $roleNama = $user->getRoleNames()->first() ?? 'staf'; @endphp
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full 
                                    @if($roleNama === 'admin') bg-red-100 text-red-700
                                    @elseif($roleNama === 'kabid') bg-purple-100 text-purple-700
                                    @elseif($roleNama === 'kasi') bg-indigo-100 text-indigo-700
                                    @else bg-blue-100 text-blue-700
                                    @endif">{{ ucfirst($roleNama) }}</span>
                            </td>
                            <td class="px-6 py-3">
                                @if($user->is_active ?? true)
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Aktif</span>
                                @else
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center">
                                <div class="flex items-center justify-center gap-2 text-xs">
                                    <a href="{{ route('internal.pengguna.edit', $user) }}" class="text-primary-500 hover:underline font-medium">Edit</a>
                                    @if(Auth::id() !== $user->id)
                                        <form method="POST" action="{{ route('internal.pengguna.destroy', $user) }}" onsubmit="return confirm('Hapus pengguna ini?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:underline font-medium">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada pengguna</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection