<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    protected $guarded = [];

    protected $casts = ['expiry_date' => 'datetime', 'mfg_date' => 'date', 'pack_date' => 'date'];

    public function item() { return $this->belongsTo(Item::class); }

    /** Sisa umur simpan dalam persen dari umur simpan total item, atau null bila tidak bisa dihitung. */
    public function remainingLifePct(?Item $item = null): ?float
    {
        $item ??= $this->item;
        if (! $this->expiry_date || ! $item) {
            return null;
        }
        $life = $item->shelfLife();
        $left = max(0, now()->diffInSeconds($this->expiry_date, false) / 86400);

        return $life ? $left / $life * 100 : null;
    }

    public function daysLeft(): ?int
    {
        return $this->expiry_date ? (int) floor(now()->diffInSeconds($this->expiry_date, false) / 86400) : null;
    }

    public function isExpired(): bool { return $this->expiry_date && $this->expiry_date->isPast(); }
}
