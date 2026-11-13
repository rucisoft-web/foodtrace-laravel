<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $guarded = [];

    protected $casts = ['roles' => 'array', 'pinned' => 'bool', 'starts_at' => 'date', 'ends_at' => 'date'];

    public const LEVELS = ['info' => 'Info', 'penting' => 'Penting', 'darurat' => 'Darurat'];

    public function user() { return $this->belongsTo(User::class); }

    public function scopeActive($q)
    {
        $today = now()->toDateString();

        return $q->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', $today));
    }

    public function roleLabels(): array
    {
        return $this->roles ? array_map(fn ($r) => User::ROLES[$r] ?? $r, $this->roles) : ['Semua peran'];
    }
}
