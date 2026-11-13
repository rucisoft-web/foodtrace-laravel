<?php

namespace App\Http\Controllers;

use App\Models\BarcodeTemplate;
use App\Models\Brand;
use App\Models\Item;
use App\Models\Location;
use App\Models\Partner;
use App\Models\ShipTo;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Support\Wms;
use App\Support\WmsException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Master data: Item, Brand SAP, Satuan SAP, Bin/Rak, Source & Destination (+ShipTo), template barcode karton, zona & gudang. */
class MasterController extends Controller
{
    // ---------- Item ----------
    public function items(Request $r)
    {
        $q = Item::with('uom', 'brand')->when($r->zone, fn ($q, $z) => $q->where('zone', $z))->when($r->group, fn ($q, $g) => $q->where('item_group', $g))
            ->when($r->brand, fn ($q, $b) => $q->where('brand_id', $b))
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")->orWhere('barcode', $s)))
            ->when($r->flag === 'cw', fn ($q) => $q->where('catch_weight', true))->when($r->flag === 'qc', fn ($q) => $q->where('auto_quarantine', true))
            ->when($r->flag === 'inactive', fn ($q) => $q->where('active', false), fn ($q) => $q->where('active', true))->orderBy('code');
        if ($this->wantsCsv($r)) {
            return $this->csv('item-master', ['ItemCode', 'Nama', 'Barcode', 'Group', 'Sub group', 'Brand', 'Satuan', 'Zona', 'Berat aktual', 'Umur simpan (hari)', 'Min. umur terima %', 'Auto karantina', 'Min stok', 'Harga pokok'],
                $q->get()->map(fn ($i) => [$i->code, $i->name, $i->barcode, $i->item_group, $i->sub_group, $i->brand?->label(), $i->uom->code, $i->zone, $i->catch_weight ? 'ya' : '', $i->shelf_life_days, $i->min_receive_life_pct, $i->auto_quarantine ? 'ya' : '', $i->min_stock, $i->cost]));
        }

        return view('master.items', ['rows' => $q->paginate(100)->withQueryString(), 'groups' => Item::distinct()->orderBy('item_group')->pluck('item_group')->filter(),
            'brands' => Brand::orderBy('code')->get()]);
    }

    public function item(string $item)
    {
        $item = $item === 'new' ? new Item(['zone' => 'frozen', 'picking_strategy' => 'fefo', 'active' => true, 'quarantine_days' => 7]) : Item::findOrFail($item);

        return view('master.item', ['item' => $item->load('barcodeTemplates'), 'uoms' => Uom::orderBy('code')->get(), 'brands' => Brand::orderBy('code')->get(),
            'templates' => BarcodeTemplate::orderBy('sequence')->get(), 'onHand' => $item->exists ? $item->onHand() : 0]);
    }

    public function itemSave(Request $r, ?Item $item = null)
    {
        $item ??= new Item;
        $data = $r->validate([
            'code' => ['required', 'max:40', Rule::unique('items')->ignore($item->id)], 'name' => 'required|max:255', 'barcode' => 'nullable|max:60',
            'item_group' => 'nullable|max:60', 'sub_group' => 'nullable|max:60', 'brand_id' => 'nullable|exists:brands,id', 'uom_id' => 'required|exists:uoms,id',
            'zone' => 'required|in:frozen,chilled,dry', 'country' => 'nullable|max:60', 'cost' => 'nullable|numeric', 'shelf_life_days' => 'nullable|integer|min:0',
            'min_receive_life_pct' => 'nullable|integer|min:0|max:100', 'quarantine_days' => 'nullable|integer|min:0', 'units_per_pallet' => 'nullable|numeric',
            'picking_strategy' => 'required|in:fefo,fifo', 'min_stock' => 'nullable|numeric', 'weight_kg' => 'nullable|numeric',
        ]);
        foreach (['catch_weight', 'auto_quarantine', 'auto_release', 'multi_qty', 'active'] as $f) {
            $data[$f] = $r->boolean($f);
        }
        foreach (['cost', 'shelf_life_days', 'min_receive_life_pct', 'quarantine_days', 'units_per_pallet', 'min_stock', 'weight_kg'] as $f) {
            $data[$f] = $data[$f] ?? 0;
        }
        $item->fill($data)->save();
        $item->barcodeTemplates()->sync((array) $r->templates);

        return redirect()->route('items.edit', $item)->with('ok', "Item {$item->code} disimpan.");
    }

    // ---------- Brand & satuan ----------
    public function brands(Request $r)
    {
        $rows = Brand::withCount('items')->when($r->q, fn ($q, $s) => $q->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"))->orderBy('code')->get();

        return view('master.brands', compact('rows'));
    }

    public function brandSave(Request $r)
    {
        $b = $r->id ? Brand::findOrFail($r->id) : new Brand;
        $data = $r->validate(['code' => ['required', 'max:20', Rule::unique('brands')->ignore($b->id)], 'name' => 'required|max:120']);
        $b->fill($data + ['active' => $r->boolean('active', true)])->save();

        return back()->with('ok', "Brand {$b->label()} disimpan.");
    }

    public function uoms()
    {
        return view('master.uoms', ['rows' => Uom::orderBy('code')->get(), 'used' => Item::selectRaw('uom_id, COUNT(*) n')->groupBy('uom_id')->pluck('n', 'uom_id')]);
    }

    public function uomSave(Request $r)
    {
        $u = $r->id ? Uom::findOrFail($r->id) : new Uom;
        $data = $r->validate(['code' => ['required', 'max:10', Rule::unique('uoms')->ignore($u->id)], 'name' => 'required|max:40']);
        $u->fill($data + ['is_weight' => $r->boolean('is_weight')])->save();

        return back()->with('ok', "Satuan {$u->code} disimpan.");
    }

    // ---------- Lokasi ----------
    public function locations(Request $r)
    {
        $rows = Location::withCount(['stocks as live' => fn ($q) => $q->where('qty', '>', 0)])
            ->when($r->zone, fn ($q, $z) => $q->where('eff_zone', $z))->when($r->area, fn ($q, $a) => $q->where('area', $a))
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('full_name', 'like', "%{$s}%")->orWhere('barcode', 'like', "%{$s}%")))
            ->orderBy('full_name')->get();
        $parents = Location::where('is_bin', false)->orderBy('full_name')->get();

        return view('master.locations', ['rows' => $rows, 'parents' => $parents, 'edit' => $r->edit ? Location::find($r->edit) : null]);
    }

    public function locationSave(Request $r)
    {
        $loc = $r->id ? Location::findOrFail($r->id) : new Location;
        $data = $r->validate(['name' => 'required|max:40', 'parent_id' => 'nullable|exists:locations,id', 'barcode' => ['nullable', 'max:60', Rule::unique('locations')->ignore($loc->id)],
            'zone' => 'nullable|in:frozen,chilled,dry', 'area' => ['nullable', Rule::in(array_keys(Wms::AREAS))], 'max_pallets' => 'nullable|integer|min:0', 'max_weight' => 'nullable|numeric']);
        if ($loc->exists && $data['parent_id'] && in_array((int) $data['parent_id'], $loc->descendantIds())) {
            throw new WmsException('Induk tidak boleh lokasi itu sendiri atau turunannya.');
        }
        $data['warehouse_id'] = $data['parent_id'] ? Location::find($data['parent_id'])->warehouse_id : ($loc->warehouse_id ?? Warehouse::orderBy('id')->value('id'));
        $loc->fill($data + ['is_bin' => $r->boolean('is_bin'), 'active' => $r->boolean('active', true), 'max_pallets' => $data['max_pallets'] ?? 0, 'max_weight' => $data['max_weight'] ?? 0])->save();
        if ($loc->wasChanged('full_name') || $loc->wasChanged('eff_zone')) {
            $this->resaveChildren($loc); // hitung ulang nama lengkap & zona efektif turunan
        }

        return redirect()->route('locations.index', ['q' => $loc->barcode ?: $loc->name])->with('ok', "Lokasi {$loc->full_name} disimpan.");
    }

    private function resaveChildren(Location $loc): void
    {
        foreach ($loc->children as $child) {
            $child->save();
            $this->resaveChildren($child);
        }
    }

    public function locationLabels(Request $r)
    {
        $locs = Location::when($r->ids, fn ($q) => $q->whereIn('id', explode(',', $r->ids)))
            ->when(! $r->ids && $r->q, fn ($q) => $q->where('full_name', 'like', "%{$r->q}%")->orWhere('barcode', 'like', "%{$r->q}%"))
            ->whereNotNull('barcode')->orderBy('full_name')->limit(300)->get();

        return view('print.location-labels', compact('locs'));
    }

    // ---------- Partner ----------
    public function partners(Request $r, string $kind)
    {
        $rows = Partner::withCount('shipTos')->where($kind === 'source' ? 'is_source' : 'is_destination', true)
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")->orWhere('city', 'like', "%{$s}%")))
            ->when($r->group, fn ($q, $g) => $q->where('shipping_group', $g))->orderBy('name')->paginate(100)->withQueryString();
        $groups = $kind === 'destination' ? Partner::whereNotNull('shipping_group')->distinct()->orderBy('shipping_group')->pluck('shipping_group') : collect();

        return view('master.partners', compact('rows', 'kind', 'groups'));
    }

    public function partner(Request $r, string $partner)
    {
        $partner = $partner === 'new' ? new Partner(['is_source' => $r->kind === 'source', 'is_destination' => $r->kind !== 'source', 'active' => true]) : Partner::findOrFail($partner);
        $partner->load('shipTos');
        $docs = $partner->exists ? $partner->documents()->latest('id')->limit(15)->get() : collect();

        return view('master.partner', compact('partner', 'docs'));
    }

    public function partnerSave(Request $r, ?Partner $partner = null)
    {
        $partner ??= new Partner;
        $data = $r->validate(['code' => ['required', 'max:30', Rule::unique('partners')->ignore($partner->id)], 'name' => 'required|max:255', 'owner_code' => 'nullable|max:30',
            'min_shelf_life_pct' => 'nullable|integer|min:0|max:100', 'over_receive_pct' => 'nullable|numeric|min:0|max:100', 'shipping_group' => 'nullable|max:40',
            'address' => 'nullable|max:255', 'city' => 'nullable|max:80', 'phone' => 'nullable|max:40']);
        $partner->fill($data + ['is_source' => $r->boolean('is_source'), 'is_destination' => $r->boolean('is_destination'), 'active' => $r->boolean('active', true),
            'min_shelf_life_pct' => $data['min_shelf_life_pct'] ?? 0, 'over_receive_pct' => $data['over_receive_pct'] ?? 0])->save();

        return redirect()->route('partners.edit', $partner)->with('ok', "{$partner->name} disimpan.");
    }

    public function shipToSave(Request $r, Partner $partner)
    {
        if ($r->delete) {
            $s = $partner->shipTos()->findOrFail($r->delete);
            if (\App\Models\Document::where('ship_to_id', $s->id)->exists()) {
                throw new WmsException("ShipTo {$s->name} sudah dipakai di dokumen; tidak bisa dihapus.");
            }
            $s->delete();

            return back()->with('ok', 'ShipTo dihapus.');
        }
        $data = $r->validate(['name' => 'required|max:255', 'code' => 'nullable|max:60', 'address' => 'nullable|max:255', 'city' => 'nullable|max:80', 'phone' => 'nullable|max:40']);
        $s = $r->id ? $partner->shipTos()->findOrFail($r->id) : new ShipTo(['partner_id' => $partner->id]);
        $s->fill($data)->save();

        return back()->with('ok', "ShipTo {$s->name} disimpan.");
    }

    // ---------- Template barcode ----------
    public function templates(Request $r)
    {
        return view('master.templates', ['rows' => BarcodeTemplate::withCount('items')->orderBy('sequence')->get(), 'edit' => $r->edit ? BarcodeTemplate::find($r->edit) : null,
            'result' => session('test_result')]);
    }

    public function templateSave(Request $r)
    {
        $t = $r->id ? BarcodeTemplate::findOrFail($r->id) : new BarcodeTemplate;
        $data = $r->validate(['name' => 'required|max:255', 'kind' => 'required|in:gs1,regex', 'pattern' => 'nullable|max:255', 'weight_unit' => 'required|in:kg,lb',
            'weight_decimals' => 'required|integer|min:0|max:5', 'min_weight' => 'nullable|numeric', 'max_weight' => 'nullable|numeric', 'sample' => 'nullable|max:255', 'sequence' => 'nullable|integer']);
        if ($data['kind'] === 'regex' && @preg_match('/'.str_replace('/', '\/', (string) $data['pattern']).'/', '') === false) {
            throw new WmsException('Pola regex tidak valid.');
        }
        $t->fill($data + ['active' => $r->boolean('active', true), 'min_weight' => $data['min_weight'] ?? 0, 'max_weight' => $data['max_weight'] ?? 0, 'sequence' => $data['sequence'] ?? 10])->save();

        return redirect()->route('templates.index')->with('ok', "Template {$t->name} disimpan.");
    }

    public function templateTest(Request $r)
    {
        $t = BarcodeTemplate::findOrFail($r->id);
        try {
            $res = ['ok' => true, 'data' => $t->parse((string) $r->barcode)];
        } catch (WmsException $e) {
            $res = ['ok' => false, 'error' => $e->getMessage()];
        }

        return back()->with('test_result', $res + ['template' => $t->name, 'barcode' => $r->barcode]);
    }

    // ---------- Zona & gudang ----------
    public function zones()
    {
        return view('master.zones', ['zones' => Zone::orderBy('sequence')->get(), 'wh' => Warehouse::orderBy('id')->first(),
            'stats' => Location::where('is_bin', true)->selectRaw('eff_zone, area, COUNT(*) n')->groupBy('eff_zone', 'area')->get()->groupBy('eff_zone')]);
    }

    public function zoneSave(Request $r)
    {
        foreach ((array) $r->zones as $id => $z) {
            Zone::whereKey($id)->update(['name' => $z['name'], 'code' => strtoupper($z['code']), 'shelf_life_days' => (int) $z['shelf_life_days'], 'color' => $z['color']]);
        }

        return back()->with('ok', 'Standar zona disimpan.');
    }

    public function warehouse()
    {
        return redirect()->route('zones.index');
    }

    public function warehouseSave(Request $r)
    {
        $data = $r->validate(['name' => 'required|max:255', 'sap_code' => 'nullable|max:20', 'address' => 'nullable|max:255']);
        Warehouse::orderBy('id')->first()->update($data);
        foreach (['company_name', 'company_city', 'sap_company_db', 'sap_mode'] as $k) {
            if ($r->has($k)) {
                \App\Models\Setting::put($k, $r->input($k));
            }
        }

        return back()->with('ok', 'Data gudang & perusahaan disimpan.');
    }
}
