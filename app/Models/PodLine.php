<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PodLine extends Model
{
    protected $guarded = [];

    protected $casts = ['qty_sent' => 'float', 'qty_received' => 'float'];

    public function item() { return $this->belongsTo(Item::class); }
}
