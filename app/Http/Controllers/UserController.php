<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/** Pengguna & peran (hanya Administrator) dan ganti kata sandi sendiri. */
class UserController extends Controller
{
    public function index(Request $r)
    {
        $this->requireAdmin();

        return view('users.index', ['rows' => User::orderBy('role')->orderBy('name')->get(), 'edit' => $r->edit ? User::find($r->edit) : null]);
    }

    public function save(Request $r, ?User $user = null)
    {
        $this->requireAdmin();
        $user ??= new User;
        $data = $r->validate([
            'username' => ['required', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->ignore($user->id)], 'name' => 'required|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)], 'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$user->exists ? 'nullable' : 'required', 'min:8'],
        ]);
        if ($user->id === auth()->id() && $data['role'] !== 'admin') {
            return back()->withErrors('Anda tidak bisa menurunkan peran akun sendiri.');
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->fill($data + ['active' => $user->id === auth()->id() ? true : $r->boolean('active')])->save();

        return redirect()->route('users.index')->with('ok', "Pengguna {$user->name} disimpan.");
    }

    public function passwordForm()
    {
        return view('users.password');
    }

    public function password(Request $r)
    {
        $r->validate(['current' => 'required', 'password' => 'required|min:8|confirmed']);
        if (! Hash::check($r->current, auth()->user()->password)) {
            return back()->withErrors('Kata sandi lama salah.');
        }
        auth()->user()->update(['password' => $r->password]);

        return back()->with('ok', 'Kata sandi diganti.');
    }
}
