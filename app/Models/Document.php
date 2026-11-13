<?php

namespace App\Models;

use App\Support\Wms;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $guarded = [];

    protected $attributes = ['state' => 'open', 'stage' => 'open'];

    protected $casts = ['scheduled_at' => 'datetime', 'done_at' => 'datetime'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function partner() { return $this->belongsTo(Partner::class); }
    public function shipTo() { return $this->belongsTo(ShipTo::class); }
    public function owner() { return $this->belongsTo(Partner::class, 'owner_id'); }
    public function dock() { return $this->belongsTo(Location::class, 'dock_id'); }
    public function staging() { return $this->belongsTo(Location::class, 'staging_location_id'); }
    public function syncDocument() { return $this->belongsTo(SyncDocument::class); }
    public function pickingJob() { return $this->belongsTo(PickingJob::class); }
    public function loading() { return $this->belongsTo(Loading::class); }
    public function lines() { return $this->hasMany(DocumentLine::class)->orderBy('line_no')->orderBy('id'); }
    public function details() { return $this->hasMany(DocumentDetail::class); }
    public function pod() { return $this->hasOne(Pod::class); }
    public function sapPostings() { return $this->hasMany(SapPosting::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function isOpen(): bool { return in_array($this->state, ['open', 'ready']); }

    public function stateLabel(): string { return Wms::DOC_STATES[$this->state] ?? $this->state; }

    public function stageLabel(): string { return Wms::OUT_STAGES[$this->stage] ?? $this->stage; }

    public function typeLabel(): string
    {
        return $this->type === 'inbound' ? (Wms::INBOUND_TYPES[$this->inbound_type] ?? 'Inbound')
            : ($this->type === 'outbound' ? (Wms::OUTBOUND_TYPES[$this->outbound_type] ?? 'Outbound') : ($this->reason ?: 'Internal'));
    }

    public function destinationName(): string
    {
        return $this->shipTo?->name ?: ($this->partner?->name ?? '-');
    }

    public function sapState(): string
    {
        return $this->sapPostings->first()?->state ?? 'none';
    }

    public function show(): string
    {
        return route($this->type === 'inbound' ? 'inbound.show' : ($this->type === 'outbound' ? 'outbound.show' : 'internal.show'), $this);
    }
}
