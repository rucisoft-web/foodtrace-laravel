<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentDetail extends Model
{
    protected $guarded = [];

    protected $casts = ['qty' => 'float', 'picked' => 'bool', 'checked' => 'bool', 'loaded' => 'bool'];

    public function document() { return $this->belongsTo(Document::class); }
    public function line() { return $this->belongsTo(DocumentLine::class, 'document_line_id'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function lot() { return $this->belongsTo(Lot::class); }
    public function stock() { return $this->belongsTo(Stock::class); }
    public function from() { return $this->belongsTo(Location::class, 'from_location_id'); }
    public function to() { return $this->belongsTo(Location::class, 'to_location_id'); }
    public function lpn() { return $this->belongsTo(Lpn::class); }
    public function resultLpn() { return $this->belongsTo(Lpn::class, 'result_lpn_id'); }

    public function anyLpn(): ?Lpn { return $this->resultLpn ?: $this->lpn; }
}
