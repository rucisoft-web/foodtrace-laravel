<?php

namespace App\Support;

use App\Models\Document;
use App\Models\InventoryCheck;
use App\Models\Lpn;
use App\Models\SapPosting;
use App\Models\Stock;
use App\Models\SyncDocument;

/** Angka kecil di menu samping: pekerjaan yang menunggu. */
class NavCounts
{
    public static function get(): array
    {
        static $c;

        return $c ??= [
            'receipts' => Document::where('type', 'inbound')->whereIn('state', ['open', 'ready'])->count(),
            'deliveries' => Document::where('type', 'outbound')->whereIn('state', ['open', 'ready'])->whereDate('scheduled_at', '<=', now())->count(),
            'putaway' => Stock::where('qty', '>', 0)->whereHas('location', fn ($q) => $q->whereIn('area', ['rcv', 'in']))->count(),
            'hold' => Stock::where('qty', '>', 0)->whereHas('location', fn ($q) => $q->where('area', 'qc'))->count(),
            'reprint' => Lpn::where('need_reprint', true)->count(),
            'ic' => InventoryCheck::where('state', 'submitted')->count(),
            'sync_err' => SyncDocument::where('state', 'error')->count(),
            'sap_failed' => SapPosting::where('state', 'failed')->count(),
        ];
    }
}
