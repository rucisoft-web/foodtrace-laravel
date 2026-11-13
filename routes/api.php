<?php

use App\Http\Controllers\SapController;
use Illuminate\Support\Facades\Route;

// Sinkron SAP -> WMS. Body = isi file POR/SOR (list JSON); header X-WMS-Token.
Route::post('/v1/sync', [SapController::class, 'api'])->middleware('throttle:120,1');
