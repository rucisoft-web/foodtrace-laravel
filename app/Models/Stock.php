<?php

namespace App\Models;

use App\Support\Wms;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $guarded = [];

    protected $casts = ['qty' => 'float', 'reserved_qty' => 'float', 'counted_qty' => 'float', 'in_date' => 'datetime'];

    public function item() { return $this->belongsTo(Item::class); }
    public function lot() { return $this->belongsTo(Lot::class); }
    public function lpn() { return $this->belongsTo(Lpn::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function owner() { return $this->belongsTo(Partner::class, 'owner_id'); }

    public function status(): string { return Wms::AREA_STATUS[$this->location?->area] ?? 'available'; }

    public function available(): float { return round($this->qty - $this->reserved_qty, 3); }

    public function scopeLive($q) { return $q->where('qty', '>', 0); }

    public function scopeStatus($q, string $status)
    {
        $areas = array_keys(array_filter(Wms::AREA_STATUS, fn ($s) => $s === $status));

        return $q->whereHas('location', fn ($l) => $status === 'available'
            ? $l->where(fn ($w) => $w->whereNotIn('area', array_keys(Wms::AREA_STATUS))->orWhereNull('area'))
            : $l->whereIn('area', $areas));
    }
}
