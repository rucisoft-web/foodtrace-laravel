<?php

use App\Models\Stock;
use App\Services\SapService;
use App\Services\StockService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('foodtrace:sap-post', function () {
    $this->info(SapService::postQueue().' posting SAP terkirim.');
})->purpose('Kirim antrean posting SAP B1 (GRPO, Delivery, Transfer)');

Artisan::command('foodtrace:auto-release', function () {
    $n = 0;
    $stocks = Stock::with('item', 'location.parent')->where('qty', '>', 0)
        ->whereHas('location', fn ($q) => $q->where('area', 'qc'))->whereHas('item', fn ($q) => $q->where('auto_release', true))->get();
    foreach ($stocks as $s) {
        if ($s->in_date && $s->in_date->copy()->addDays($s->item->quarantine_days)->lte(now())) {
            $dest = \App\Models\Location::where('parent_id', $s->location->parent_id)->where('area', 'pck')->first() ?? $s->location->parent;
            StockService::transfer($s, $dest, $s->qty, 'Auto release karantina');
            $n++;
        }
    }
    $this->info("{$n} stok dilepas dari karantina.");
})->purpose('Lepas karantina otomatis untuk item ber-Auto release setelah durasi karantina habis');

Schedule::command('foodtrace:sap-post')->everyFiveMinutes();
Schedule::command('foodtrace:auto-release')->hourly();
