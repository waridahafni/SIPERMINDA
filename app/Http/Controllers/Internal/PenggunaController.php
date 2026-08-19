<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class PenggunaController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->latest()->paginate(15);

        return view('internal.pengguna.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();

        return view('internal.pengguna.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return redirect()->route('internal.pengguna.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();

        return view('internal.pengguna.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()],
            'role' => 'required|exists:roles,name',
        ]);

        $akanAktif = $request->boolean('is_active');

        if ($user->is(Auth::user()) && ! $akanAktif) {
            return redirect()->back()->with('error', 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.');
        }

        $menghapusAdminTerakhir = $user->hasRole('admin')
            && (! $akanAktif || $request->role !== 'admin')
            && User::role('admin')->where('is_active', true)->count() <= 1;

        if ($menghapusAdminTerakhir) {
            return redirect()->back()->with('error', 'Admin aktif terakhir tidak dapat dinonaktifkan atau diubah perannya.');
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $akanAktif,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return redirect()->route('internal.pengguna.index')->with('success', 'Pengguna berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        if ($user->is(Auth::user())) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        if ($user->hasRole('admin') && User::role('admin')->where('is_active', true)->count() <= 1) {
            return redirect()->back()->with('error', 'Admin aktif terakhir tidak dapat dihapus.');
        }

        $user->delete();

        return redirect()->route('internal.pengguna.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
