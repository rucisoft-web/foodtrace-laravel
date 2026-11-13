<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncDocument extends Model
{
    protected $guarded = [];

    protected $attributes = ['state' => 'new'];

    protected $casts = ['doc_date' => 'date', 'delivery_date' => 'date'];

    public const STATES = ['new' => 'Baru', 'done' => 'Jadi dokumen', 'error' => 'Gagal'];

    public function document() { return $this->belongsTo(Document::class); }
}
