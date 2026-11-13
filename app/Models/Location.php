<?php

namespace App\Models;

use App\Support\Wms;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $guarded = [];

    protected $casts = ['is_bin' => 'bool', 'active' => 'bool'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function parent() { return $this->belongsTo(Location::class, 'parent_id'); }
    public function children() { return $this->hasMany(Location::class, 'parent_id'); }
    public function stocks() { return $this->hasMany(Stock::class); }

    protected static function booted(): void
    {
        static::saving(function (Location $loc) {
            $parent = $loc->parent_id ? Location::find($loc->parent_id) : null;
            $loc->full_name = $parent ? $parent->full_name.'/'.$loc->name : ($loc->warehouse?->code ?? Warehouse::find($loc->warehouse_id)?->code).'/'.$loc->name;
            $loc->eff_zone = $loc->zone ?: $parent?->eff_zone;
        });
    }

    public function status(): string { return Wms::AREA_STATUS[$this->area] ?? 'available'; }

    public function areaLabel(): string { return Wms::AREAS[$this->area] ?? '-'; }

    public function code(): string { return $this->barcode ?: $this->full_name; }

    /** Semua id lokasi turunan (termasuk dirinya). */
    public function descendantIds(): array
    {
        return Location::where('full_name', $this->full_name)->orWhere('full_name', 'like', $this->full_name.'/%')->pluck('id')->all();
    }

    /** Lokasi karantina zona tertentu di gudang yang sama. */
    public function qcLocation(?string $zone): ?Location
    {
        return Location::where('warehouse_id', $this->warehouse_id)->where('area', 'qc')
            ->when($zone, fn ($q) => $q->where('eff_zone', $zone))->orderBy('id')->first();
    }
}
