<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Penomoran dokumen per prefix dan bulan, mis. RCV/2611/0001. */
class Seq
{
    public static function next(string $prefix, int $pad = 4, ?\DateTimeInterface $at = null): string
    {
        $at ??= now();
        $code = $prefix.'/'.$at->format('ym').'/';

        return DB::transaction(function () use ($code, $pad) {
            $row = DB::table('sequences')->where('code', $code)->lockForUpdate()->first();
            $n = $row ? $row->next : 1;
            DB::table('sequences')->updateOrInsert(['code' => $code], ['next' => $n + 1]);

            return $code.str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
        });
    }

    /** Nomor LPN global: SKI0000001. */
    public static function lpn(): string
    {
        return DB::transaction(function () {
            $prefix = \App\Models\Warehouse::orderBy('id')->value('code') ?: 'LPN';
            $row = DB::table('sequences')->where('code', 'LPN')->lockForUpdate()->first();
            $n = $row ? $row->next : 1;
            DB::table('sequences')->updateOrInsert(['code' => 'LPN'], ['next' => $n + 1]);

            return $prefix.str_pad((string) $n, 7, '0', STR_PAD_LEFT);
        });
    }
}
