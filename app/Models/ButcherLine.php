<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ButcherLine extends Model
{
    protected $guarded = [];

    protected $casts = ['qty' => 'float', 'qty_kg' => 'float', 'expiry_date' => 'datetime'];

    public function butcher() { return $this->belongsTo(Butcher::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function lot() { return $this->belongsTo(Lot::class); }
    public function lpn() { return $this->belongsTo(Lpn::class); }
    public function location() { return $this->belongsTo(Location::class); }
}
