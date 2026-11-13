<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Potongan tampilan kecil: badge zona, status, tanggal. */
class Ui
{
    public static function zone(?string $zone): string
    {
        if (! $zone) {
            return '';
        }

        return '<span class="zone '.e($zone).'" title="'.e(Wms::ZONES[$zone] ?? $zone).'">'.e(['frozen' => 'FZ', 'chilled' => 'CH', 'dry' => 'DR'][$zone] ?? strtoupper(substr($zone, 0, 2))).'</span>';
    }

    public static function status(?string $s): string
    {
        if (! $s) {
            return '';
        }
        $cls = ['receiving' => 'b-navy', 'available' => 'b-green', 'picked' => 'b-brown', 'hold' => 'b-amber', 'damage' => 'b-red',
            'open' => 'b-navy', 'ready' => 'b-green', 'done' => 'b-grey', 'cancel' => 'b-red', 'new' => 'b-navy', 'error' => 'b-red',
            'queued' => 'b-navy', 'posted' => 'b-green', 'failed' => 'b-red', 'draft' => 'b-grey', 'submitted' => 'b-amber',
            'approved' => 'b-green', 'rejected' => 'b-red', 'close' => 'b-grey', 'in_progress' => 'b-navy', 'picking' => 'b-navy',
            'staged' => 'b-brown', 'checked' => 'b-green', 'loading' => 'b-amber', 'shipped' => 'b-grey', 'delivered' => 'b-green',
            'quarantine' => 'b-amber', 'waste' => 'b-red'][$s] ?? 'b-grey';
        $label = Wms::STOCK_STATUS[$s] ?? Wms::OUT_STAGES[$s] ?? Wms::DOC_STATES[$s] ?? [
            'new' => 'Baru', 'error' => 'Gagal', 'queued' => 'Antre', 'posted' => 'Terkirim', 'failed' => 'Gagal', 'draft' => 'Draft',
            'submitted' => 'Menunggu approval', 'approved' => 'Approved', 'rejected' => 'Rejected', 'close' => 'Close', 'in_progress' => 'Berjalan',
            'quarantine' => 'Karantina', 'waste' => 'Waste', 'none' => 'Belum'][$s] ?? ucfirst($s);

        return '<span class="badge '.$cls.'">'.e($label).'</span>';
    }

    public static function date($v, bool $time = false): string
    {
        if (! $v) {
            return '-';
        }
        $c = $v instanceof Carbon ? $v : Carbon::parse($v);

        return $c->format($time ? 'd/m/Y H:i' : 'd/m/Y');
    }
}
