<?php

namespace App\Models;

use App\Support\Wms;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $guarded = [];

    protected $casts = [
        'catch_weight' => 'bool', 'auto_quarantine' => 'bool', 'auto_release' => 'bool', 'multi_qty' => 'bool',
        'active' => 'bool', 'cost' => 'float', 'last_price' => 'float', 'min_stock' => 'float',
        'units_per_pallet' => 'float', 'weight_kg' => 'float',
    ];

    public function brand() { return $this->belongsTo(Brand::class); }
    public function uom() { return $this->belongsTo(Uom::class); }
    public function stocks() { return $this->hasMany(Stock::class); }
    public function lots() { return $this->hasMany(Lot::class); }
    public function barcodeTemplates() { return $this->belongsToMany(BarcodeTemplate::class)->orderBy('sequence'); }

    public function label(): string { return $this->code.' · '.$this->name; }

    public function zoneLabel(): string { return Wms::ZONES[$this->zone] ?? $this->zone; }

    /** Umur simpan total (hari): dari item, atau bawaan standar zona. */
    public function shelfLife(): int
    {
        return $this->shelf_life_days ?: (int) (Zone::where('zone', $this->zone)->value('shelf_life_days') ?: 365);
    }

    public function onHand(): float
    {
        return (float) $this->stocks()->whereHas('location', fn ($q) => $q->where('area', '!=', 'prd'))->sum('qty');
    }

    public function isWeight(): bool { return (bool) $this->uom?->is_weight; }
}
