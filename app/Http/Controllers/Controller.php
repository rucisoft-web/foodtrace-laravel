<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function requireManager(): void
    {
        abort_unless(auth()->user()?->isManager(), 403, 'Hanya Kepala gudang / QA atau Administrator.');
    }

    protected function requireAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Hanya Administrator.');
    }

    /** Ekspor CSV (dibuka di Excel) dari baris array. */
    protected function csv(string $name, array $head, iterable $rows)
    {
        return response()->streamDownload(function () use ($head, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $head, ';', escape: '\\');
            foreach ($rows as $r) {
                fputcsv($out, $r, ';', escape: '\\');
            }
            fclose($out);
        }, $name.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    protected function wantsCsv(Request $r): bool
    {
        return $r->query('export') === 'csv';
    }
}
