<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carton extends Model
{
    protected $guarded = [];

    protected $attributes = ['state' => 'available'];

    protected $casts = ['weight_kg' => 'float', 'weight_lb' => 'float', 'manual_weight' => 'bool'];

    public const STATES = ['available' => 'Tersedia', 'quarantine' => 'Karantina', 'shipped' => 'Terkirim', 'waste' => 'Waste'];

    public function item() { return $this->belongsTo(Item::class); }
    public function lot() { return $this->belongsTo(Lot::class); }
    public function lpn() { return $this->belongsTo(Lpn::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function inDocument() { return $this->belongsTo(Document::class, 'in_document_id'); }
    public function outDocument() { return $this->belongsTo(Document::class, 'out_document_id'); }
}
