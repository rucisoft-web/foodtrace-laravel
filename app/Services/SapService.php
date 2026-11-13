<?php

namespace App\Services;

use App\Models\Document;
use App\Models\SapPosting;
use App\Models\Setting;

/** Antrean posting ke SAP B1. Mode simulasi: tandai terkirim dan beri DocNum; gagal bila item tanpa ItemCode. */
class SapService
{
    public static function docType(Document $doc): string
    {
        return match ($doc->type) {
            'inbound' => $doc->inbound_type === 'retur' ? 'return' : 'grpo',
            'outbound' => 'delivery',
            default => 'transfer',
        };
    }

    public static function enqueue(Document $doc): ?SapPosting
    {
        if ($doc->type === 'internal') {
            return null; // pemindahan antar-bin dalam satu gudang SAP tidak diposting
        }
        $ref = 'WMS-'.$doc->number;
        if (SapPosting::where('ref', $ref)->exists()) {
            return null;
        }
        $details = $doc->details()->with('item.uom')->get();

        return SapPosting::create([
            'ref' => $ref, 'document_id' => $doc->id, 'doc_type' => self::docType($doc),
            'sap_company_db' => Setting::get('sap_company_db', 'SARKUL'),
            'total_qty' => round($details->sum('qty'), 3),
            'total_weight' => round($details->filter(fn ($d) => $d->item->uom->is_weight)->sum('qty'), 3),
            'created_at' => StockService::now(), 'updated_at' => StockService::now(),
        ]);
    }

    public static function post($postings): int
    {
        $n = 0;
        foreach ($postings as $p) {
            if ($p->state === 'posted') {
                continue;
            }
            $p->attempts++;
            $missing = $p->document?->lines()->with('item')->get()->filter(fn ($l) => ! $l->item->code)->pluck('item.name') ?? collect();
            if ($missing->isNotEmpty()) {
                $p->fill(['state' => 'failed', 'error' => 'Item tanpa ItemCode SAP: '.$missing->implode(', ')])->save();
                continue;
            }
            $p->fill(['state' => 'posted', 'error' => null, 'posted_at' => StockService::now(), 'sap_doc_num' => (string) (100000 + $p->id)])->save();
            $n++;
        }

        return $n;
    }

    public static function postQueue(): int
    {
        return self::post(SapPosting::where('state', 'queued')->limit(200)->get());
    }
}
