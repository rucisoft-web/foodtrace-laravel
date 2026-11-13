<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pod extends Model
{
    protected $guarded = [];

    protected $attributes = ['state' => 'open'];

    protected $casts = ['received_at' => 'datetime', 'has_difference' => 'bool'];

    public function document() { return $this->belongsTo(Document::class); }
    public function lines() { return $this->hasMany(PodLine::class); }

    public function url(): string { return route('pod.public', $this->token); }
}
