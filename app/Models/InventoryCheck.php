<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCheck extends Model
{
    protected $guarded = [];

    protected $attributes = ['state' => 'draft', 'mode' => 'location'];

    protected $casts = ['date' => 'datetime'];

    public const STATES = ['draft' => 'Draft', 'submitted' => 'Menunggu approval', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public function lines() { return $this->hasMany(InventoryCheckLine::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function lpn() { return $this->belongsTo(Lpn::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }

    public function differenceCount(): int { return $this->lines->filter->isDifference()->count(); }

    public function title(): string { return $this->mode === 'lpn' ? 'LPN '.($this->lpn?->name) : ($this->location?->full_name ?? '-'); }
}
