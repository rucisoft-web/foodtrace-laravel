<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /** Masuk dengan nama pengguna atau email. */
    public function login(Request $request)
    {
        $d = $request->validate(['login' => 'required|string', 'password' => 'required|string']);
        $field = str_contains($d['login'], '@') ? 'email' : 'username';
        if (Auth::attempt([$field => $d['login'], 'password' => $d['password'], 'active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended($request->boolean('hht') ? route('hht.home') : route('dashboard'));
        }

        return redirect(route('home').'#masuk')->withInput($request->only('login'))->withErrors(['login' => 'Nama pengguna/email atau kata sandi salah.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
