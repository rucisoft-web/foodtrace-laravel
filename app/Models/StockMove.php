<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMove extends Model
{
    protected $guarded = [];

    protected $casts = ['date' => 'datetime', 'qty' => 'float', 'value' => 'float'];

    public function document() { return $this->belongsTo(Document::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function lot() { return $this->belongsTo(Lot::class); }
    public function from() { return $this->belongsTo(Location::class, 'from_location_id'); }
    public function to() { return $this->belongsTo(Location::class, 'to_location_id'); }
    public function lpn() { return $this->belongsTo(Lpn::class); }
    public function resultLpn() { return $this->belongsTo(Lpn::class, 'result_lpn_id'); }
    public function owner() { return $this->belongsTo(Partner::class, 'owner_id'); }
    public function partner() { return $this->belongsTo(Partner::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function fromName(): string { return $this->from?->full_name ?? ($this->from_label ?? '-'); }

    public function toName(): string { return $this->to?->full_name ?? ($this->to_label ?? '-'); }
}
