<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLine extends Model
{
    protected $guarded = [];

    protected $casts = ['qty_demand' => 'float'];

    public function document() { return $this->belongsTo(Document::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function uom() { return $this->belongsTo(Uom::class); }
    public function details() { return $this->hasMany(DocumentDetail::class); }

    public function qtyDone(): float { return round((float) $this->details->sum('qty'), 3); }

    public function qtyPicked(): float { return round((float) $this->details->where('picked', true)->sum('qty'), 3); }
}
