<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Item;
use App\Models\Location;
use App\Models\Lpn;
use App\Support\WmsException;

/** Pencarian dari hasil scan: lokasi, LPN, item, dokumen. */
class Lookup
{
    public static function location(?string $code): Location
    {
        $code = trim((string) $code);
        if ($code === '') {
            throw new WmsException('Scan barcode lokasi.');
        }
        $loc = Location::where('barcode', $code)->first()
            ?? Location::where('full_name', $code)->first()
            ?? Location::where('name', $code)->orderBy('id')->first();
        if (! $loc) {
            throw new WmsException("Lokasi {$code} tidak dikenal.");
        }

        return $loc;
    }

    public static function lpn(?string $code): Lpn
    {
        $code = trim((string) $code);
        $code = preg_replace('/^LPN-/i', '', $code);
        $lpn = Lpn::where('name', $code)->first();
        if (! $lpn) {
            throw new WmsException("LPN {$code} tidak dikenal.");
        }

        return $lpn;
    }

    public static function item(?string $code): ?Item
    {
        $code = trim((string) $code);

        return Item::where('code', $code)->orWhere('barcode', $code)->first();
    }

    public static function document(?string $code, string $type): Document
    {
        $code = trim((string) $code);
        $doc = Document::where('type', $type)->whereIn('state', ['open', 'ready'])
            ->where(fn ($q) => $q->where('number', $code)->orWhere('origin', $code)->orWhere('origin', 'like', '%-'.$code))->first();
        if (! $doc) {
            throw new WmsException("Dokumen {$code} tidak ditemukan atau sudah selesai.");
        }

        return $doc;
    }
}
