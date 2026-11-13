<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;

class LandingController extends Controller
{
    public function index()
    {
        $anns = collect();
        try {
            $anns = Announcement::active()->orderByDesc('pinned')->orderByDesc('created_at')->limit(30)->get();
        } catch (\Throwable) {
        }
        $role = request('peran');
        if ($role && isset(User::ROLES[$role])) {
            $anns = $anns->filter(fn ($a) => ! $a->roles || in_array($role, $a->roles));
        }

        return view('landing', ['anns' => $anns, 'role' => $role]);
    }
}
