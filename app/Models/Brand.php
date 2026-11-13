<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $guarded = [];
    public function items() { return $this->hasMany(Item::class); }

    public function label(): string { return $this->code.' - '.$this->name; }
}
