<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SapPosting extends Model
{
    protected $guarded = [];

    protected $attributes = ['state' => 'queued', 'attempts' => 0];

    protected $casts = ['posted_at' => 'datetime', 'total_qty' => 'float', 'total_weight' => 'float'];

    public const TYPES = ['grpo' => 'Goods Receipt PO', 'delivery' => 'Delivery', 'transfer' => 'Inventory Transfer',
        'adjustment' => 'Inventory Adjustment', 'return' => 'Return'];
    public const STATES = ['queued' => 'Antre', 'posted' => 'Terkirim', 'failed' => 'Gagal'];

    public function document() { return $this->belongsTo(Document::class); }
}
