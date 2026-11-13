<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Item;
use App\Models\Partner;
use App\Models\ShipTo;
use App\Models\SyncDocument;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Support\Seq;
use App\Support\WmsException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Inbox dokumen SAP B1: POR (PO inbound) dan SOR (SO outbound) diubah menjadi dokumen gudang. Idempoten per nomor. */
class SyncService
{
    public static function sapDate(?string $text): ?string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }
        try {
            return Carbon::createFromFormat('m/d/Y', $text)->toDateString();
        } catch (\Throwable) {
            return Carbon::parse($text)->toDateString();
        }
    }

    /** Terima isi file sinkron (list JSON atau satu objek). Mengembalikan koleksi SyncDocument. */
    public static function importJson($text, bool $process = true)
    {
        $data = is_string($text) ? json_decode($text, true, 512, JSON_THROW_ON_ERROR) : $text;
        if (isset($data['number'])) {
            $data = [$data];
        }
        $docs = collect();
        foreach ($data as $doc) {
            if (empty($doc['number'])) {
                throw new \InvalidArgumentException('field "number" wajib ada');
            }
            $existing = SyncDocument::where('number', $doc['number'])->first();
            $docs->push($existing ?? SyncDocument::create(self::valsFromPayload($doc)));
        }
        if ($process) {
            foreach ($docs->where('state', '!=', 'done') as $d) {
                self::process($d);
            }
        }

        return $docs->map->fresh();
    }

    public static function valsFromPayload(array $doc): array
    {
        $info = ($doc['additional_information'] ?? [[]])[0] ?? [];
        $out = array_key_exists('outbound_type', $doc);

        return [
            'number' => $doc['number'], 'direction' => $out ? 'outbound' : 'inbound',
            'doc_type' => $doc['outbound_type'] ?? $doc['inbound_type'] ?? null, 'obj_type' => $doc['obj_type'] ?? null,
            'web_trans_no' => $info['web_trans_no'] ?? null, 'doc_entry' => $info['doc_entry'] ?? null,
            'partner_code' => $out ? ($doc['destination_code'] ?? null) : ($doc['source'] ?? null),
            'partner_name' => $doc['destination_outlet'] ?? null, 'warehouse_code' => $doc['warehouse'] ?? null,
            'owner_code' => $doc['owner'] ?? null, 'shipping_group' => $doc['shipping_group'] ?? null,
            'vendor_ref' => ($info['vendor_ref_no'] ?? null) ?: ($info['vendor_do_no'] ?? null),
            'container_no' => ($info['container'] ?? null) ?: null,
            'doc_date' => self::sapDate($doc['po_date'] ?? $doc['date'] ?? null),
            'delivery_date' => self::sapDate($doc['delivery_date'] ?? $doc['date'] ?? null),
            'priority' => $doc['priority'] ?? null, 'sap_status' => $doc['status'] ?? null,
            'line_count' => count($doc['items'] ?? []),
            'payload' => json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => StockService::now(), 'updated_at' => StockService::now(),
        ];
    }

    public static function mapType(string $direction, ?string $text): string
    {
        $text = strtolower((string) $text);
        $table = $direction === 'inbound'
            ? ['purchase' => 'po', 'titip' => 'titipan', 'transfer' => 'transfer', 'retur' => 'retur', 'return' => 'retur', 'butcher' => 'butcher', 'potong' => 'butcher', 'sampl' => 'sampling']
            : ['sales' => 'so', 'butcher' => 'butcher', 'produksi' => 'butcher', 'musnah' => 'dispose', 'dispose' => 'dispose', 'transfer' => 'transfer', 'vendor' => 'rtv', 'chill' => 'chill_frozen'];
        foreach ($table as $k => $code) {
            if (str_contains($text, $k)) {
                return $code;
            }
        }

        return $direction === 'inbound' ? 'po' : 'others';
    }

    /** Satuan baris SAP: dipakai bila sama dengan satuan item; satuan lain diabaikan (tanpa konversi palsu). */
    public static function uomFor(?string $code, Item $item): int
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '' || $code === $item->uom->code) {
            return $item->uom_id;
        }
        $alias = ['KGS' => 'KG', 'GR' => 'GRAM', 'G' => 'GRAM'][$code] ?? $code;
        $uom = Uom::where('code', $alias)->first();

        return $uom && $uom->is_weight && $item->uom->is_weight && $uom->id === $item->uom_id ? $uom->id : $item->uom_id;
    }

    public static function process(SyncDocument $sd): void
    {
        try {
            DB::transaction(function () use ($sd) {
                $payload = json_decode($sd->payload, true);
                $codes = collect($payload['items'] ?? [])->pluck('item_code')->all();
                $items = Item::with('uom')->whereIn('code', $codes)->get()->keyBy('code');
                $missing = collect($codes)->unique()->reject(fn ($c) => $items->has($c))->sort()->values();
                if ($missing->isNotEmpty()) {
                    throw new WmsException('ItemCode belum ada di WMS: '.$missing->implode(', '));
                }
                $wh = Warehouse::where('sap_code', $sd->warehouse_code)->first() ?? Warehouse::orderBy('id')->first();
                if (! $wh) {
                    throw new WmsException("Gudang {$sd->warehouse_code} tidak ditemukan.");
                }
                $out = $sd->direction === 'outbound';
                $partner = null;
                if ($sd->partner_code) {
                    $partner = Partner::firstOrCreate(['code' => $sd->partner_code], ['name' => $sd->partner_name ?: $sd->partner_code]);
                    $partner->update([$out ? 'is_destination' : 'is_source' => true]);
                }
                $shipTo = null;
                $outlet = trim((string) $sd->partner_name);
                if ($out && $partner && $outlet !== '' && strcasecmp($outlet, $partner->name) !== 0) {
                    $shipTo = ShipTo::firstOrCreate(['partner_id' => $partner->id, 'name' => $outlet]);
                }
                $owner = null;
                if ($sd->owner_code) {
                    $o = Partner::where('owner_code', $sd->owner_code)->first();
                    // Owner = perusahaan pemilik gudang berarti stok sendiri (tanpa owner).
                    $owner = $o && $o->owner_code !== $wh->sap_code ? $o : null;
                }
                $doc = Document::create([
                    'number' => Seq::next($out ? 'DO' : 'RCV', 4, StockService::now()), 'type' => $sd->direction, 'warehouse_id' => $wh->id,
                    'origin' => $sd->number, 'partner_id' => $partner?->id, 'ship_to_id' => $shipTo?->id, 'owner_id' => $owner?->id,
                    'scheduled_at' => $sd->delivery_date ?? StockService::now(), 'state' => 'open', 'sync_document_id' => $sd->id,
                    'shipping_group' => $sd->shipping_group ?: ($out ? $partner?->shipping_group : null), 'priority' => $sd->priority,
                    'vendor_ref' => $sd->vendor_ref, 'container_no' => $sd->container_no,
                    'inbound_type' => $out ? null : self::mapType('inbound', $sd->doc_type),
                    'outbound_type' => $out ? self::mapType('outbound', $sd->doc_type) : null,
                    'created_at' => StockService::now(), 'updated_at' => StockService::now(),
                ]);
                foreach ($payload['items'] as $i => $line) {
                    $item = $items[$line['item_code']];
                    $doc->lines()->create(['item_id' => $item->id, 'uom_id' => self::uomFor($line['uom_id'] ?? null, $item),
                        'qty_demand' => (float) $line['qty'], 'description' => trim((string) ($line['remark'] ?? '')) ?: null, 'line_no' => $i]);
                }
                $sd->update(['state' => 'done', 'document_id' => $doc->id, 'error' => null]);
            });
        } catch (WmsException $e) {
            $sd->update(['state' => 'error', 'error' => $e->getMessage()]);
        }
    }
}
