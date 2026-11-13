<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $guarded = [];

    protected $casts = ['is_source' => 'bool', 'is_destination' => 'bool', 'active' => 'bool', 'over_receive_pct' => 'float'];

    public function shipTos() { return $this->hasMany(ShipTo::class)->orderBy('name'); }
    public function documents() { return $this->hasMany(Document::class); }

    public function label(): string { return $this->code.' · '.$this->name; }
}
