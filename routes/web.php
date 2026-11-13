<?php

use App\Http\Controllers as C;
use Illuminate\Support\Facades\Route;

Route::get('/install', [C\InstallController::class, 'show'])->name('install');
Route::post('/install', [C\InstallController::class, 'store']);

Route::get('/', [C\LandingController::class, 'index'])->name('home');
Route::get('/login', fn () => redirect()->route('home').'#masuk')->name('login');
Route::post('/login', [C\AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [C\AuthController::class, 'logout'])->name('logout');

// Halaman POD untuk driver (tanpa login, lewat QR di surat jalan).
Route::match(['get', 'post'], '/pod/{token}', [C\PodController::class, 'public'])->name('pod.public');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [C\DashboardController::class, 'index'])->name('dashboard');

    // Inbound
    Route::get('/inbound', [C\InboundController::class, 'index'])->name('inbound.index');
    Route::get('/inbound/{document}', [C\InboundController::class, 'show'])->name('inbound.show');
    Route::post('/inbound/{document}/header', [C\InboundController::class, 'header'])->name('inbound.header');
    Route::post('/inbound/{document}/scan', [C\InboundController::class, 'scan'])->name('inbound.scan');
    Route::post('/inbound/{document}/pallet/{lpn}', [C\InboundController::class, 'closePallet'])->name('inbound.pallet');
    Route::delete('/inbound/{document}/detail/{detail}', [C\InboundController::class, 'removeDetail'])->name('inbound.detail.delete');
    Route::post('/inbound/{document}/validate', [C\InboundController::class, 'validateDoc'])->name('inbound.validate');
    Route::post('/inbound/{document}/cancel', [C\InboundController::class, 'cancel'])->name('inbound.cancel');
    Route::get('/cartons', [C\StockController::class, 'cartons'])->name('cartons.index');
    Route::get('/putaway', [C\PutawayController::class, 'index'])->name('putaway.index');
    Route::post('/putaway', [C\PutawayController::class, 'move'])->name('putaway.move');
    Route::get('/internal', [C\PutawayController::class, 'log'])->name('internal.index');
    Route::get('/internal/{document}', [C\PutawayController::class, 'show'])->name('internal.show');
    Route::get('/qc', [C\QcController::class, 'index'])->name('qc.index');
    Route::post('/qc/hold', [C\QcController::class, 'hold'])->name('qc.hold');
    Route::get('/qc/release/{stock}', [C\QcController::class, 'releaseForm'])->name('qc.release');
    Route::post('/qc/release/{stock}', [C\QcController::class, 'release']);

    // Inventory
    Route::get('/stock', [C\StockController::class, 'index'])->name('stock.index');
    Route::get('/lpn', [C\StockController::class, 'lpns'])->name('lpn.index');
    Route::get('/lpn/{lpn}', [C\StockController::class, 'lpn'])->name('lpn.show');
    Route::get('/label/lpn', [C\StockController::class, 'labels'])->name('lpn.labels');
    Route::get('/stock-card', [C\StockController::class, 'card'])->name('stock.card');
    Route::get('/cycle-count', [C\StockController::class, 'cycle'])->name('cycle.index');
    Route::post('/cycle-count', [C\StockController::class, 'cycleSave'])->name('cycle.save');
    Route::post('/cycle-count/apply', [C\StockController::class, 'cycleApply'])->name('cycle.apply');
    Route::resource('inventory-checks', C\InventoryCheckController::class)->only(['index', 'create', 'store', 'show'])->names('ic');
    Route::post('/inventory-checks/{check}/submit', [C\InventoryCheckController::class, 'submit'])->name('ic.submit');
    Route::post('/inventory-checks/{check}/approve', [C\InventoryCheckController::class, 'approve'])->name('ic.approve');
    Route::post('/inventory-checks/{check}/reject', [C\InventoryCheckController::class, 'reject'])->name('ic.reject');
    Route::post('/inventory-checks/{check}/reset', [C\InventoryCheckController::class, 'reset'])->name('ic.reset');

    // Outbound
    Route::get('/outbound', [C\OutboundController::class, 'index'])->name('outbound.index');
    Route::post('/outbound/job', [C\OutboundController::class, 'createJob'])->name('outbound.job');
    Route::get('/outbound/{document}', [C\OutboundController::class, 'show'])->name('outbound.show');
    Route::post('/outbound/{document}/reserve', [C\OutboundController::class, 'reserve'])->name('outbound.reserve');
    Route::post('/outbound/{document}/unreserve', [C\OutboundController::class, 'unreserve'])->name('outbound.unreserve');
    Route::post('/outbound/{document}/issue', [C\OutboundController::class, 'issue'])->name('outbound.issue');
    Route::get('/outbound/{document}/surat-jalan', [C\OutboundController::class, 'suratJalan'])->name('outbound.sj');
    Route::get('/picking-jobs', [C\PickingController::class, 'index'])->name('jobs.index');
    Route::get('/picking-jobs/{job}', [C\PickingController::class, 'show'])->name('jobs.show');
    Route::post('/picking-jobs/{job}/pick/{detail}', [C\PickingController::class, 'pick'])->name('jobs.pick');
    Route::post('/picking-jobs/{job}/stage', [C\PickingController::class, 'stage'])->name('jobs.stage');
    Route::get('/checker', [C\PickingController::class, 'checker'])->name('checker.index');
    Route::get('/checker/{document}', [C\PickingController::class, 'check'])->name('checker.show');
    Route::post('/checker/{document}', [C\PickingController::class, 'checkScan']);
    Route::get('/loading', [C\LoadingController::class, 'index'])->name('loading.index');
    Route::post('/loading', [C\LoadingController::class, 'store'])->name('loading.store');
    Route::get('/loading/{loading}', [C\LoadingController::class, 'show'])->name('loading.show');
    Route::post('/loading/{loading}/scan', [C\LoadingController::class, 'scan'])->name('loading.scan');
    Route::post('/loading/{loading}/close', [C\LoadingController::class, 'close'])->name('loading.close');
    Route::post('/loading/{loading}/cancel', [C\LoadingController::class, 'cancel'])->name('loading.cancel');
    Route::get('/loading/{loading}/manifest', [C\LoadingController::class, 'manifest'])->name('loading.manifest');
    Route::get('/loading/{loading}/surat-jalan', [C\LoadingController::class, 'suratJalan'])->name('loading.sj');
    Route::get('/pod', [C\PodController::class, 'index'])->name('pod.index');
    Route::get('/pod-admin/{pod}', [C\PodController::class, 'show'])->name('pod.show');

    // Butcher
    Route::get('/butcher', [C\ButcherController::class, 'index'])->name('butcher.index');
    Route::get('/butcher/create', [C\ButcherController::class, 'create'])->name('butcher.create');
    Route::post('/butcher', [C\ButcherController::class, 'store'])->name('butcher.store');
    Route::get('/butcher/{butcher}', [C\ButcherController::class, 'show'])->name('butcher.show');
    Route::post('/butcher/{butcher}/done', [C\ButcherController::class, 'done'])->name('butcher.done');
    Route::post('/butcher/{butcher}/cancel', [C\ButcherController::class, 'cancel'])->name('butcher.cancel');

    // Laporan
    Route::get('/reports/{report}', [C\ReportController::class, 'show'])->name('reports.show');

    // Integrasi SAP
    Route::get('/sap/sync', [C\SapController::class, 'sync'])->name('sap.sync');
    Route::post('/sap/sync', [C\SapController::class, 'import'])->name('sap.import');
    Route::get('/sap/sync/{sync}', [C\SapController::class, 'syncShow'])->name('sap.sync.show');
    Route::post('/sap/sync/{sync}/process', [C\SapController::class, 'reprocess'])->name('sap.sync.process');
    Route::get('/sap/queue', [C\SapController::class, 'queue'])->name('sap.queue');
    Route::post('/sap/queue/post', [C\SapController::class, 'post'])->name('sap.post');

    // Master data
    Route::get('/master/items', [C\MasterController::class, 'items'])->name('items.index');
    Route::get('/master/items/{item}', [C\MasterController::class, 'item'])->name('items.edit');
    Route::post('/master/items/{item?}', [C\MasterController::class, 'itemSave'])->name('items.save');
    Route::get('/master/brands', [C\MasterController::class, 'brands'])->name('brands.index');
    Route::post('/master/brands', [C\MasterController::class, 'brandSave'])->name('brands.save');
    Route::get('/master/uoms', [C\MasterController::class, 'uoms'])->name('uoms.index');
    Route::post('/master/uoms', [C\MasterController::class, 'uomSave'])->name('uoms.save');
    Route::get('/master/locations', [C\MasterController::class, 'locations'])->name('locations.index');
    Route::post('/master/locations', [C\MasterController::class, 'locationSave'])->name('locations.save');
    Route::get('/master/locations/labels', [C\MasterController::class, 'locationLabels'])->name('locations.labels');
    Route::get('/master/partners/{kind}', [C\MasterController::class, 'partners'])->name('partners.index')->whereIn('kind', ['source', 'destination']);
    Route::get('/master/partner/{partner}', [C\MasterController::class, 'partner'])->name('partners.edit');
    Route::post('/master/partner/{partner?}', [C\MasterController::class, 'partnerSave'])->name('partners.save');
    Route::post('/master/partner/{partner}/shipto', [C\MasterController::class, 'shipToSave'])->name('partners.shipto');
    Route::get('/master/barcode-templates', [C\MasterController::class, 'templates'])->name('templates.index');
    Route::post('/master/barcode-templates', [C\MasterController::class, 'templateSave'])->name('templates.save');
    Route::post('/master/barcode-templates/test', [C\MasterController::class, 'templateTest'])->name('templates.test');
    Route::get('/master/zones', [C\MasterController::class, 'zones'])->name('zones.index');
    Route::post('/master/zones', [C\MasterController::class, 'zoneSave'])->name('zones.save');
    Route::get('/master/warehouse', [C\MasterController::class, 'warehouse'])->name('warehouse.edit');
    Route::post('/master/warehouse', [C\MasterController::class, 'warehouseSave'])->name('warehouse.save');
    Route::get('/users', [C\UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user?}', [C\UserController::class, 'save'])->name('users.save');
    Route::get('/announcements', [C\AnnouncementController::class, 'index'])->name('ann.index');
    Route::post('/announcements/{announcement?}', [C\AnnouncementController::class, 'save'])->name('ann.save');
    Route::delete('/announcements/{announcement}', [C\AnnouncementController::class, 'destroy'])->name('ann.delete');
    Route::get('/password', [C\UserController::class, 'passwordForm'])->name('password');
    Route::post('/password', [C\UserController::class, 'password']);

    // Panduan, implementasi
    Route::get('/panduan', [C\HelpController::class, 'index'])->name('help.index');
    Route::get('/panduan/{key}', [C\HelpController::class, 'show'])->name('help.show');
    Route::get('/implementasi', [C\HelpController::class, 'implementation'])->name('implementation');

    // Handheld (HHT)
    Route::prefix('hht')->name('hht.')->group(function () {
        Route::get('/', [C\HhtController::class, 'home'])->name('home');
        Route::get('/receipts', [C\HhtController::class, 'receipts'])->name('receipts');
        Route::get('/receipt/{document}', [C\HhtController::class, 'receipt'])->name('receipt');
        Route::post('/receipt/{document}', [C\HhtController::class, 'receiptPost']);
        Route::match(['get', 'post'], '/putaway', [C\HhtController::class, 'putaway'])->name('putaway');
        Route::get('/jobs', [C\HhtController::class, 'jobs'])->name('jobs');
        Route::get('/pick/{job}', [C\HhtController::class, 'pick'])->name('pick');
        Route::post('/pick/{job}', [C\HhtController::class, 'pickPost']);
        Route::match(['get', 'post'], '/check', [C\HhtController::class, 'check'])->name('check');
        Route::get('/loadings', [C\HhtController::class, 'loadings'])->name('loadings');
        Route::match(['get', 'post'], '/loading/{loading}', [C\HhtController::class, 'loading'])->name('loading');
        Route::match(['get', 'post'], '/ic', [C\HhtController::class, 'ic'])->name('ic');
        Route::match(['get', 'post'], '/butcher', [C\HhtController::class, 'butcher'])->name('butcher');
        Route::match(['get', 'post'], '/hold', [C\HhtController::class, 'hold'])->name('hold');
        Route::match(['get', 'post'], '/count', [C\HhtController::class, 'count'])->name('count');
        Route::match(['get', 'post'], '/lookup', [C\HhtController::class, 'lookup'])->name('lookup');
    });
});
