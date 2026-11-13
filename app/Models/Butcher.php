<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Butcher extends Model
{
    protected $guarded = [];

    protected $attributes = ['state' => 'draft', 'kind' => 'out', 'total_kg' => 0];

    protected $casts = ['date' => 'datetime', 'total_kg' => 'float'];

    public const TYPES = ['rebag' => 'Rebag', 'nonrebag' => 'Non rebag', 'trimming' => 'Trimming', 'sisa_potong' => 'Sisa potong', 'repack' => 'Repack'];
    public const STATES = ['draft' => 'Draft', 'done' => 'Selesai', 'cancel' => 'Batal'];

    public function lines() { return $this->hasMany(ButcherLine::class); }
    public function source() { return $this->belongsTo(Butcher::class, 'source_id'); }
    public function results() { return $this->hasMany(Butcher::class, 'source_id'); }
    public function user() { return $this->belongsTo(User::class); }

    /** Yield = berat hasil (Butcher In selesai) / berat bahan (Butcher Out). */
    public function yieldPct(): float
    {
        $out = $this->kind === 'out' ? $this : $this->source;
        if (! $out || ! $out->total_kg) {
            return 0.0;
        }

        return round($out->results()->where('state', 'done')->sum('total_kg') / $out->total_kg * 100, 1);
    }
}
