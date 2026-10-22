<?php

use App\Http\Controllers\AllocationController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DiaryController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HerdController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\LabourController;
use App\Http\Controllers\LandController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MilkController;
use App\Http\Controllers\MoneyController;
use App\Http\Controllers\OfflineController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\ProfitController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

// The বাংলা | English switch works before login too.
Route::post('locale', LocaleController::class)->middleware('throttle:30,1')->name('locale');

// "হয়ে গেছে" / "১ ঘণ্টা পরে" pressed on a notification: the link itself is signed, no session needed.
Route::post('reminders/{reminder}/act/{action}', [ReminderController::class, 'act'])
    ->middleware(['signed', 'throttle:30,1'])->name('reminders.act');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::inertia('guide', 'guide/Index')->name('guide');
    // An investor sees only the projects they put money into, nothing else.
    Route::get('investor', [InvestorController::class, 'index'])->middleware('can:investor-view')->name('investor.index');
    Route::get('search', SearchController::class)->middleware('throttle:120,1')->name('search');

    // Reminders and phone notifications: everyone signed in can set and receive their own.
    Route::get('reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::post('reminders', [ReminderController::class, 'store'])->middleware('throttle:60,1')->name('reminders.store');
    Route::put('reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
    Route::post('push/subscribe', [ReminderController::class, 'subscribe'])->middleware('throttle:20,1')->name('push.subscribe');
    Route::post('push/unsubscribe', [ReminderController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::post('push/test', [ReminderController::class, 'test'])->middleware('throttle:10,1')->name('push.test');
    Route::put('notify-prefs', [ReminderController::class, 'prefs'])->name('notify.prefs');

    // Daily work: milk and feed use. Open to the entry role.
    Route::middleware('can:post-daily')->group(function () {
        Route::get('milk', [MilkController::class, 'index'])->name('milk.index');
        Route::post('milk', [MilkController::class, 'store'])->name('milk.store');
        Route::get('stock/move', [StockController::class, 'create'])->name('stock.create');
        Route::post('stock/issue', [StockController::class, 'issue'])->name('stock.issue');
        Route::get('offline', [OfflineController::class, 'show'])->name('offline.show');
        Route::post('offline/sync', [OfflineController::class, 'sync'])->middleware('throttle:30,1')->name('offline.sync');
        Route::post('animals/{animal}/breedings', [HerdController::class, 'storeBreeding'])->name('breedings.store');
        Route::put('breedings/{breeding}', [HerdController::class, 'updateBreeding'])->name('breedings.update');
        Route::post('animals/{animal}/weights', [HerdController::class, 'storeWeight'])->name('weights.store');
        Route::post('health', [HealthController::class, 'store'])->name('health.store');
        Route::post('prices', [PriceController::class, 'store'])->name('prices.store');
        Route::post('labour/logs', [LabourController::class, 'storeLogs'])->name('labour.logs.store');
        Route::delete('labour/logs/{log}', [LabourController::class, 'destroyLog'])->name('labour.logs.destroy');
        Route::delete('prices/{price}', [PriceController::class, 'destroy'])->name('prices.destroy');
        Route::delete('health/{event}', [HealthController::class, 'destroy'])->name('health.destroy');
        Route::post('diary/notes', [DiaryController::class, 'storeNote'])->name('diary.notes.store');
        Route::put('diary/notes/{note}', [DiaryController::class, 'updateNote'])->name('diary.notes.update');
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
        Route::get('reports/usage', [ReportController::class, 'usage'])->name('reports.usage');
        Route::get('reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
        Route::inertia('guide/split', 'guide/Split')->name('guide.split');
        Route::get('money', [MoneyController::class, 'index'])->name('money.index');
        Route::get('breeding', [HerdController::class, 'breeding'])->name('breeding.index');
        Route::get('batches', [HerdController::class, 'batches'])->name('batches.index');
        Route::get('diary', [DiaryController::class, 'index'])->name('diary.index');
        Route::get('health', [HealthController::class, 'index'])->name('health.index');
        Route::get('land', [LandController::class, 'index'])->name('land.index');
        Route::get('profit', [ProfitController::class, 'index'])->name('profit.index');
        Route::get('prices', [PriceController::class, 'index'])->name('prices.index');
        Route::get('labour', [LabourController::class, 'index'])->name('labour.index');
        Route::get('cash', [CashController::class, 'index'])->name('cash.index');
        Route::get('summary', [SummaryController::class, 'index'])->name('summary.index');
        Route::get('debts', [DebtController::class, 'index'])->name('debts.index');
        Route::get('land/seasons/{season}', [LandController::class, 'season'])->name('land.season');
        Route::get('diary/files/{file}', [DiaryController::class, 'file'])->name('diary.files.show');
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
        Route::post('batches', [HerdController::class, 'storeBatch'])->name('batches.store');
        Route::post('debts', [DebtController::class, 'store'])->name('debts.store');
        Route::post('debts/{debt}/move', [DebtController::class, 'move'])->name('debts.move');
        Route::put('debts/{debt}/plan', [DebtController::class, 'plan'])->name('debts.plan');
        Route::post('labour/workers', [LabourController::class, 'storeWorker'])->name('labour.workers.store');
        Route::put('labour/workers/{worker}', [LabourController::class, 'updateWorker'])->name('labour.workers.update');
        Route::post('labour/workers/{worker}/pay', [LabourController::class, 'pay'])->name('labour.workers.pay');
        Route::post('land/plots', [LandController::class, 'storePlot'])->name('land.plots.store');
        Route::put('land/plots/{plot}', [LandController::class, 'updatePlot'])->name('land.plots.update');
        Route::post('land/seasons', [LandController::class, 'storeSeason'])->name('land.seasons.store');
        Route::put('land/seasons/{season}', [LandController::class, 'updateSeason'])->name('land.seasons.update');
        Route::post('land/seasons/{season}/harvest', [LandController::class, 'harvest'])->name('land.seasons.harvest');
        Route::post('land/items', [LandController::class, 'storeItem'])->name('land.items.store');
        Route::put('batches/{batch}', [HerdController::class, 'updateBatch'])->name('batches.update');
        Route::post('diary/goals', [DiaryController::class, 'storeGoal'])->name('diary.goals.store');
        Route::put('diary/goals/{goal}', [DiaryController::class, 'updateGoal'])->name('diary.goals.update');
    });

    Route::post('entries/{entry}/reverse', [EntryController::class, 'reverse'])->middleware('can:reverse-entries')->name('entries.reverse');

    Route::middleware('can:manage-setup')->prefix('setup')->name('setup.')->group(function () {
        Route::post('centers', [CostCenterController::class, 'store'])->name('centers.store');
        Route::put('centers/{center}', [CostCenterController::class, 'update'])->name('centers.update');
        Route::get('centers', [CostCenterController::class, 'manage'])->name('centers.manage');
        Route::put('land/bigha', [LandController::class, 'bigha'])->name('land.bigha');
        Route::post('centers/{center}/toggle', [CostCenterController::class, 'toggle'])->name('centers.toggle');
        Route::post('centers/{center}/move', [CostCenterController::class, 'move'])->name('centers.move');
        Route::delete('centers/{center}', [CostCenterController::class, 'destroy'])->name('centers.destroy');
        Route::post('items', [StockController::class, 'storeItem'])->name('items.store');
        Route::put('items/{item}', [StockController::class, 'updateItem'])->name('items.update');
        Route::get('accounts', [SetupController::class, 'accounts'])->name('accounts');
        Route::post('accounts', [SetupController::class, 'storeAccount'])->name('accounts.store');
        Route::put('accounts/{account}', [SetupController::class, 'updateAccount'])->name('accounts.update');
        Route::get('parties', [SetupController::class, 'parties'])->name('parties');
        Route::post('parties', [SetupController::class, 'storeParty'])->name('parties.store');
        Route::put('parties/{party}', [SetupController::class, 'updateParty'])->name('parties.update');
        Route::put('budget/{head}', [ReportController::class, 'updateBudgetHead'])->name('budget.update');
        Route::post('money', [MoneyController::class, 'store'])->name('money.store');
    });

    Route::middleware('can:manage-roles')->prefix('setup')->name('setup.')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    Route::middleware('can:manage-update')->prefix('setup')->name('setup.')->group(function () {
        Route::get('update', [SystemController::class, 'show'])->name('update');
        Route::post('update', [SystemController::class, 'update'])->middleware('throttle:3,10')->name('update.run');
    });

    Route::middleware('can:manage-users')->prefix('setup')->name('setup.')->group(function () {
        Route::get('investors', [InvestorController::class, 'manage'])->name('investors');
        Route::post('investors', [InvestorController::class, 'grant'])->name('investors.grant');
        Route::delete('investors/{access}', [InvestorController::class, 'revoke'])->name('investors.revoke');
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
