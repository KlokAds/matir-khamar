<?php

use App\Http\Controllers\AllocationController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MilkController;
use App\Http\Controllers\OfflineController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Daily work: milk and feed use. Open to the entry role.
    Route::middleware('can:post-daily')->group(function () {
        Route::get('milk', [MilkController::class, 'index'])->name('milk.index');
        Route::post('milk', [MilkController::class, 'store'])->name('milk.store');
        Route::get('stock/move', [StockController::class, 'create'])->name('stock.create');
        Route::post('stock/issue', [StockController::class, 'issue'])->name('stock.issue');
        Route::get('offline', [OfflineController::class, 'show'])->name('offline.show');
        Route::post('offline/sync', [OfflineController::class, 'sync'])->middleware('throttle:30,1')->name('offline.sync');
    });

    Route::middleware('can:view-reports')->group(function () {
        Route::get('centers', [CostCenterController::class, 'index'])->name('centers.index');
        Route::get('centers/{center}', [CostCenterController::class, 'show'])->name('centers.show');
        Route::get('entries', [EntryController::class, 'index'])->name('entries.index');
        Route::get('entries/{entry}', [EntryController::class, 'show'])->whereNumber('entry')->name('entries.show');
        Route::get('attachments/{attachment}', [EntryController::class, 'attachment'])->name('attachments.show');
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('animals', [AnimalController::class, 'index'])->name('animals.index');
        Route::get('animals/{animal}', [AnimalController::class, 'show'])->whereNumber('animal')->name('animals.show');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/budget', [ReportController::class, 'budget'])->name('reports.budget');
    });

    Route::middleware('can:post-entries')->group(function () {
        Route::get('entries/create', [EntryController::class, 'create'])->name('entries.create');
        Route::post('entries', [EntryController::class, 'store'])->middleware('throttle:60,1')->name('entries.store');
        Route::post('entries/{entry}/attachments', [EntryController::class, 'attach'])->name('entries.attach');
        Route::post('stock/purchase', [StockController::class, 'purchase'])->name('stock.purchase');
        Route::post('stock/produce', [StockController::class, 'produce'])->name('stock.produce');
        Route::get('animals/create', [AnimalController::class, 'create'])->name('animals.create');
        Route::post('animals', [AnimalController::class, 'store'])->name('animals.store');
        Route::post('animals/{animal}/transfer', [AnimalController::class, 'transfer'])->name('animals.transfer');
        Route::post('animals/{animal}/sell', [AnimalController::class, 'sell'])->name('animals.sell');
        Route::post('animals/{animal}/death', [AnimalController::class, 'death'])->name('animals.death');
        Route::get('allocation', [AllocationController::class, 'create'])->name('allocation.create');
        Route::post('allocation', [AllocationController::class, 'store'])->name('allocation.store');
    });

    Route::post('entries/{entry}/reverse', [EntryController::class, 'reverse'])->middleware('can:reverse-entries')->name('entries.reverse');

    Route::middleware('can:manage-setup')->prefix('setup')->name('setup.')->group(function () {
        Route::post('centers', [CostCenterController::class, 'store'])->name('centers.store');
        Route::put('centers/{center}', [CostCenterController::class, 'update'])->name('centers.update');
        Route::post('items', [StockController::class, 'storeItem'])->name('items.store');
        Route::put('items/{item}', [StockController::class, 'updateItem'])->name('items.update');
        Route::get('accounts', [SetupController::class, 'accounts'])->name('accounts');
        Route::post('accounts', [SetupController::class, 'storeAccount'])->name('accounts.store');
        Route::put('accounts/{account}', [SetupController::class, 'updateAccount'])->name('accounts.update');
        Route::get('parties', [SetupController::class, 'parties'])->name('parties');
        Route::post('parties', [SetupController::class, 'storeParty'])->name('parties.store');
        Route::put('parties/{party}', [SetupController::class, 'updateParty'])->name('parties.update');
        Route::put('budget/{head}', [ReportController::class, 'updateBudgetHead'])->name('budget.update');
        Route::get('import', [ImportController::class, 'index'])->name('import');
        Route::post('import/fetch', [ImportController::class, 'fetch'])->middleware('throttle:6,1')->name('import.fetch');
        Route::post('import/post', [ImportController::class, 'post'])->middleware('throttle:6,1')->name('import.post');
        Route::put('import/mappings/{mapping}', [ImportController::class, 'updateMapping'])->name('import.mapping');
        Route::post('import/records/{record}/resolve', [ImportController::class, 'resolve'])->name('import.resolve');
        Route::post('import/records/{record}/qty', [ImportController::class, 'setQty'])->name('import.qty');
        Route::post('import/ignore-undecided', [ImportController::class, 'ignoreUndecided'])->name('import.ignore');
    });

    Route::middleware('can:manage-users')->prefix('setup')->name('setup.')->group(function () {
        Route::get('users', [SetupController::class, 'users'])->name('users');
        Route::post('users', [SetupController::class, 'storeUser'])->name('users.store');
        Route::put('users/{user}', [SetupController::class, 'updateUser'])->name('users.update');
    });

    Route::middleware('can:lock-periods')->prefix('setup')->name('setup.')->group(function () {
        Route::get('periods', [SetupController::class, 'periods'])->name('periods');
        Route::post('periods', [SetupController::class, 'lockPeriod'])->name('periods.lock');
        Route::delete('periods/{lock}', [SetupController::class, 'unlockPeriod'])->name('periods.unlock');
    });

    Route::get('setup/audit', [SetupController::class, 'audit'])->middleware('can:view-audit')->name('setup.audit');
});

require __DIR__.'/settings.php';
