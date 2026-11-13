<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCheckLine extends Model
{
    protected $guarded = [];

    protected $casts = ['qty_before' => 'float', 'qty_after' => 'float', 'expiry_before' => 'datetime', 'expiry_after' => 'datetime', 'need_reprint' => 'bool'];

    public function check() { return $this->belongsTo(InventoryCheck::class, 'inventory_check_id'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function lot() { return $this->belongsTo(Lot::class); }
    public function lpn() { return $this->belongsTo(Lpn::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function stock() { return $this->belongsTo(Stock::class); }

    public function difference(): float { return round($this->qty_after - $this->qty_before, 3); }

    public function expiryChanged(): bool
    {
        return $this->expiry_after && (! $this->expiry_before || ! $this->expiry_after->equalTo($this->expiry_before));
    }

    public function isDifference(): bool { return abs($this->difference()) > 0.0005 || $this->expiryChanged(); }
}
