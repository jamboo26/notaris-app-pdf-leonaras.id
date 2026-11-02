<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // Menampilkan daftar semua user
    public function index()
    {
        $users = User::with('roles')->latest()->paginate(10);
        return view('users.index', compact('users'));
    }

    // Menampilkan form tambah user baru
    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    // Memproses simpan user baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign Role (Manager / Notaris / Admin)
        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'User ' . $user->name . ' dengan role ' . $request->role . ' berhasil ditambahkan!');
    }

    // Menghapus user
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Mencegah admin menghapus dirinya sendiri
        if (auth()->id() == $user->id) {
            return back()->withErrors(['error' => 'Anda tidak bisa menghapus akun Anda sendiri!']);
        }

        $user->delete();
        return back()->with('success', 'User berhasil dihapus!');
    }
}
