<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Hak akses per peran: Admin mengelola pengguna & pengumuman, Kepala gudang/QA mengubah master dan menyetujui. */
class RoleGate
{
    private const ADMIN = ['users.', 'ann.'];
    private const MANAGER_WRITE = ['items.save', 'brands.save', 'uoms.save', 'locations.save', 'partners.save', 'partners.shipto', 'templates.save',
        'zones.save', 'warehouse.save', 'ic.approve', 'ic.reject', 'sap.post', 'qc.release', 'cycle.apply'];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $name = (string) $request->route()?->getName();
        if (! $user || $name === '') {
            return $next($request);
        }
        if (! $user->active) {
            auth()->logout();

            return redirect('/')->withErrors(['login' => 'Akun nonaktif.']);
        }
        foreach (self::ADMIN as $p) {
            if (str_starts_with($name, $p) && ! $user->isAdmin()) {
                abort(403, 'Hanya Administrator.');
            }
        }
        if ($request->isMethod('post') && in_array($name, self::MANAGER_WRITE, true) && ! $user->isManager()) {
            abort(403, 'Hanya Kepala gudang / QA atau Administrator.');
        }

        return $next($request);
    }
}
