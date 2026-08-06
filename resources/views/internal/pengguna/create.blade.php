@extends('layouts.internal')

@section('title', 'Tambah Pengguna')

@section('content')
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('internal.pengguna.index') }}" class="text-sm text-primary-500 hover:underline">&larr; Kembali</a>
        <h1 class="text-2xl font-bold text-gray-800">Tambah Pengguna Baru</h1>
    </div>

    <div class="max-w-lg">
        <form method="POST" action="{{ route('internal.pengguna.store') }}" class="bg-white rounded-xl shadow-sm border p-8 space-y-5">
            @csrf

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
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                    <option value="">Pilih Role</option>
                    <option value="staf" {{ old('role') == 'staf' ? 'selected' : '' }}>Staf</option>
                    <option value="kasi" {{ old('role') == 'kasi' ? 'selected' : '' }}>Kasi</option>
                    <option value="kabid" {{ old('role') == 'kabid' ? 'selected' : '' }}>Kabid</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-primary-500 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-primary-600 transition shadow">Simpan</button>
                <a href="{{ route('internal.pengguna.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">Batal</a>
            </div>
        </form>
    </div>
@endsection