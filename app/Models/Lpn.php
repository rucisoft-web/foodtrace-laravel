<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lpn extends Model
{
    protected $guarded = [];

    protected $attributes = ['closed' => false, 'need_reprint' => false];
    protected $casts = ['mfg_date' => 'date', 'pack_date' => 'date', 'closed' => 'bool', 'need_reprint' => 'bool'];

    public function item() { return $this->belongsTo(Item::class); }
    public function lot() { return $this->belongsTo(Lot::class); }
    public function stocks() { return $this->hasMany(Stock::class); }
    public function cartons() { return $this->hasMany(Carton::class); }

    public function liveStocks() { return $this->stocks()->where('qty', '>', 0); }

    public function qty(): float { return (float) $this->liveStocks()->sum('qty'); }

    public function currentLocation(): ?Location { return $this->liveStocks()->first()?->location; }

    public function status(): ?string { return $this->liveStocks()->first()?->status(); }
}
