<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loading extends Model
{
    protected $guarded = [];

    protected $attributes = ['state' => 'open'];

    protected $casts = ['date' => 'date'];

    public const STATES = ['open' => 'Open', 'close' => 'Close', 'cancel' => 'Cancel'];

    public function documents() { return $this->hasMany(Document::class); }

    public function details() { return DocumentDetail::whereIn('document_id', $this->documents()->pluck('id')); }

    public function progress(): array
    {
        $lines = $this->details()->with('item.uom')->get();

        return [
            'lines' => $lines->count(),
            'loaded' => $lines->where('loaded', true)->count(),
            'weight' => round($lines->sum(fn ($d) => $d->item->isWeight() ? $d->qty : $d->qty * $d->item->weight_kg), 2),
        ];
    }
}
