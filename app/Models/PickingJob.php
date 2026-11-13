<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickingJob extends Model
{
    protected $guarded = [];

    protected $attributes = ['state' => 'in_progress'];

    protected $casts = ['use_tote' => 'bool'];

    public const STATES = ['in_progress' => 'Berjalan', 'done' => 'Selesai', 'cancel' => 'Batal'];

    public function documents() { return $this->hasMany(Document::class); }
    public function user() { return $this->belongsTo(User::class); }

    /** Persentase siap: qty yang sudah diambil dibanding permintaan. */
    public function readyPct(): float
    {
        $ids = $this->documents()->pluck('id');
        $demand = (float) DocumentLine::whereIn('document_id', $ids)->sum('qty_demand');
        $picked = (float) DocumentDetail::whereIn('document_id', $ids)->where('picked', true)->sum('qty');

        return $demand ? round(min(100, $picked / $demand * 100), 1) : 0.0;
    }
}
