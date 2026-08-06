@extends('layouts.internal')

@section('title', 'Edit Pengguna')

@section('content')
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('internal.pengguna.index') }}" class="text-sm text-primary-500 hover:underline">&larr; Kembali</a>
        <h1 class="text-2xl font-bold text-gray-800">Edit Pengguna</h1>
    </div>

    <div class="max-w-lg">
        <form method="POST" action="{{ route('internal.pengguna.update', $user) }}" class="bg-white rounded-xl shadow-sm border p-8 space-y-5">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-gray-400 font-normal">(kosongkan jika tidak ingin mengubah)</span></label>
                <input type="password" name="password"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                <input type="password" name="password_confirmation"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                    @php $roleTerpilih = $user->getRoleNames()->first() ?? 'staf'; @endphp
                    <option value="staf" {{ old('role', $roleTerpilih) == 'staf' ? 'selected' : '' }}>Staf</option>
                    <option value="kasi" {{ old('role', $roleTerpilih) == 'kasi' ? 'selected' : '' }}>Kasi</option>
                    <option value="kabid" {{ old('role', $roleTerpilih) == 'kabid' ? 'selected' : '' }}>Kabid</option>
                    <option value="admin" {{ old('role', $roleTerpilih) == 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                    <span class="text-sm font-medium text-gray-700">Akun Aktif</span>
                </label>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-primary-500 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-primary-600 transition shadow">Update</button>
                <a href="{{ route('internal.pengguna.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">Batal</a>
            </div>
        </form>
    </div>
@endsection