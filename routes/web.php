<?php

use Illuminate\Support\Facades\Route;
use App\Models\Company;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepotStock\DepotStockController;

use App\Http\Controllers\CompanySwitcherController;
use App\Http\Controllers\Onboarding\CompanyController as OnboardingCompanyController;

use App\Http\Controllers\Settings\CompanySettingsController;
use App\Http\Controllers\Settings\DepotController;
use App\Http\Controllers\Settings\SupplierController;
use App\Http\Controllers\Settings\TransporterController;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TransporterLedgerController;
use App\Http\Controllers\SupplierLedgerController;
use App\Http\Controllers\DepotLedgerController;
use App\Http\Controllers\BatchCostController;
use App\Http\Controllers\DepotChargeConfigController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ImportNominationController;
use App\Http\Controllers\SalesController;

/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    // First-run: if no company exists, go straight to setup wizard
    if (Company::query()->count() === 0) {
        return redirect()->route('company.create');
    }

    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Onboarding (public for first-time setup)
|--------------------------------------------------------------------------
*/
Route::get('/company/create', [OnboardingCompanyController::class, 'create'])
    ->name('company.create');

Route::post('/company', [OnboardingCompanyController::class, 'store'])
    ->name('company.store');

/*
|--------------------------------------------------------------------------
| Auth routes (blocked until setup complete via company.setup middleware)
|--------------------------------------------------------------------------
*/
Route::middleware('company.setup')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Protected area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'company.setup'])->group(function () {

    // Switcher is accessible even if no active company (edge cases)
    Route::get('/companies/switcher', [CompanySwitcherController::class, 'index'])
        ->name('companies.switcher');

    Route::get('/companies/{company}/switch', [CompanySwitcherController::class, 'switch'])
        ->name('companies.switch');

    // Create extra company (modal POST) – safe-default: controller enforces owner + caps
    Route::post('/companies', [CompanySwitcherController::class, 'store'])
        ->name('companies.store');

    // Everything else requires an active company
    Route::middleware(['active.company'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/depot-stock', [DepotStockController::class, 'index'])
            ->name('depot-stock.index');
        Route::get('/depot-stock/export', [DepotStockController::class, 'exportCsv'])
            ->name('depot-stock.export');

        Route::prefix('settings')->name('settings.')->group(function () {

            Route::get('/', fn() => view('settings.hub'))->name('hub');

            Route::get('/company', [CompanySettingsController::class, 'edit'])
                ->name('company.edit');

            Route::patch('/company', [CompanySettingsController::class, 'update'])
                ->name('company.update');

            Route::get('/depots', [DepotController::class, 'index'])
                ->name('depots.index');
            Route::post('/depots', [DepotController::class, 'store'])
                ->name('depots.store');
            Route::patch('/depots/{depot}', [DepotController::class, 'update'])
                ->name('depots.update');
            Route::patch('/depots/{depot}/toggle-active', [DepotController::class, 'toggleActive'])
                ->name('depots.toggle-active');

            Route::get('/suppliers', [SupplierController::class, 'index'])
                ->name('suppliers.index');
            Route::post('/suppliers', [SupplierController::class, 'store'])
                ->name('suppliers.store');
            Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update'])
                ->name('suppliers.update');
            Route::patch('/suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])
                ->name('suppliers.toggle-active');

            Route::get('/transporters', [TransporterController::class, 'index'])
                ->name('transporters.index');
            Route::post('/transporters', [TransporterController::class, 'store'])
                ->name('transporters.store');
            Route::patch('/transporters/{transporter}', [TransporterController::class, 'update'])
                ->name('transporters.update');
            Route::patch('/transporters/{transporter}/toggle-active', [TransporterController::class, 'toggleActive'])
                ->name('transporters.toggle-active');

            Route::get('/inventory', [\App\Http\Controllers\Settings\InventorySettingsController::class, 'index'])
                ->name('inventory.index');
            Route::patch('/inventory/costing', [\App\Http\Controllers\Settings\InventorySettingsController::class, 'updateCosting'])
                ->name('inventory.update-costing');

            Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
            Route::get('/clients/export', [ClientController::class, 'exportCsv'])->name('clients.export');
            Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
            Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
            Route::patch('/clients/{client}/toggle-active', [ClientController::class, 'toggleActive'])->name('clients.toggle-active');
        });

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');



        // Products (company-scoped)
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle-active');

        // Purchases (was ago-purchases)
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/export', [PurchaseController::class, 'exportCsv'])->name('purchases.export');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::post('/purchases/{purchase}/confirm', [PurchaseController::class, 'confirm'])->name('purchases.confirm');
        Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])
            ->name('purchases.receive');
        Route::post('/purchases/{purchase}/undo-receipt', [PurchaseController::class, 'undoReceipt'])
            ->name('purchases.undo-receipt');
        Route::post('/purchases/{purchase}/cross-dock-transfer', [PurchaseController::class, 'crossDockTransfer'])
            ->name('purchases.cross-dock-transfer');
        Route::post('/purchases/{purchase}/cross-dock-dispatch', [PurchaseController::class, 'crossDockDispatch'])
            ->name('purchases.cross-dock-dispatch');
        Route::post('/purchases/{purchase}/nominate', [PurchaseController::class, 'nominate'])
            ->name('purchases.nominate');
        Route::post('/purchases/{purchase}/import-deliver', [PurchaseController::class, 'importDeliver'])
            ->name('purchases.import-deliver');
        Route::get('/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])
            ->name('purchases.edit');
        Route::patch('/purchases/{purchase}', [PurchaseController::class, 'update'])
            ->name('purchases.update');
        Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])
            ->name('purchases.cancel');
        Route::post('/purchases/{purchase}/void', [PurchaseController::class, 'void'])
            ->name('purchases.void');

        // Import logistics (nominations + truck lifecycle)
        Route::post('/purchases/{purchase}/import-nomination',
            [ImportNominationController::class, 'store'])
            ->name('purchases.import-nomination.store');

        Route::patch('/purchases/{purchase}/import-nomination/{nomination}',
            [ImportNominationController::class, 'update'])
            ->name('purchases.import-nomination.update');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks',
            [ImportNominationController::class, 'addTruck'])
            ->name('purchases.import-nomination.trucks.store');

        Route::patch('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}',
            [ImportNominationController::class, 'updateTruck'])
            ->name('purchases.import-nomination.trucks.update');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/record-load',
            [ImportNominationController::class, 'recordLoad'])
            ->name('purchases.import-nomination.trucks.record-load');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/fail-load',
            [ImportNominationController::class, 'failLoad'])
            ->name('purchases.import-nomination.trucks.fail-load');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/mark-in-transit',
            [ImportNominationController::class, 'markInTransit'])
            ->name('purchases.import-nomination.trucks.mark-in-transit');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/record-border',
            [ImportNominationController::class, 'recordBorder'])
            ->name('purchases.import-nomination.trucks.record-border');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/record-delivery',
            [ImportNominationController::class, 'recordDelivery'])
            ->name('purchases.import-nomination.trucks.record-delivery');

        Route::get('/purchases/{purchase}/import-nomination/{nomination}/truck-template',
            [ImportNominationController::class, 'truckTemplate'])
            ->name('purchases.import-nomination.trucks.template');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/import-trucks',
            [ImportNominationController::class, 'importTrucks'])
            ->name('purchases.import-nomination.trucks.import');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/quick-load-deliver',
            [ImportNominationController::class, 'quickLoadDeliver'])
            ->name('purchases.import-nomination.trucks.quick-load-deliver');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/bulk-in-transit',
            [ImportNominationController::class, 'bulkMarkInTransit'])
            ->name('purchases.import-nomination.trucks.bulk-in-transit');

        // Clients moved to settings group above

        // Transporter ledger (balance, entries, payments, statement, export)
        Route::get('/transporters', [TransporterLedgerController::class, 'index'])
            ->name('transporters.index');
        Route::get('/transporters/{transporter}/statement', [TransporterLedgerController::class, 'statement'])
            ->name('transporters.statement');
        Route::get('/transporters/{transporter}/export', [TransporterLedgerController::class, 'exportCsv'])
            ->name('transporters.export');
        Route::get('/transporters/{transporter}', [TransporterLedgerController::class, 'show'])
            ->name('transporters.show');
        Route::post('/transporters/{transporter}/payments', [TransporterLedgerController::class, 'recordPayment'])
            ->name('transporters.payments.store');

        // Supplier ledger
        Route::get('/suppliers', [SupplierLedgerController::class, 'index'])
            ->name('suppliers.index');
        Route::get('/suppliers/{supplier}/statement', [SupplierLedgerController::class, 'statement'])
            ->name('suppliers.statement');
        Route::get('/suppliers/{supplier}/export', [SupplierLedgerController::class, 'exportCsv'])
            ->name('suppliers.export');
        Route::get('/suppliers/{supplier}', [SupplierLedgerController::class, 'show'])
            ->name('suppliers.show');
        Route::post('/suppliers/{supplier}/payments', [SupplierLedgerController::class, 'recordPayment'])
            ->name('suppliers.payments.store');
        Route::post('/suppliers/{supplier}/credits', [SupplierLedgerController::class, 'recordCredit'])
            ->name('suppliers.credits.store');

        // Depot charge configs (rate cards)
        Route::post('/depots/{depot}/charge-configs', [DepotChargeConfigController::class, 'store'])
            ->name('depots.charge-configs.store');
        Route::patch('/depots/{depot}/charge-configs/{config}', [DepotChargeConfigController::class, 'update'])
            ->name('depots.charge-configs.update');
        Route::delete('/depots/{depot}/charge-configs/{config}', [DepotChargeConfigController::class, 'destroy'])
            ->name('depots.charge-configs.destroy');
        Route::patch('/depots/{depot}/charge-configs/{config}/toggle', [DepotChargeConfigController::class, 'toggleActive'])
            ->name('depots.charge-configs.toggle');

        // Depot ledger
        Route::get('/depots', [DepotLedgerController::class, 'index'])
            ->name('depots.index');
        Route::get('/depots/{depot}/statement', [DepotLedgerController::class, 'statement'])
            ->name('depots.statement');
        Route::get('/depots/{depot}/export', [DepotLedgerController::class, 'exportCsv'])
            ->name('depots.export');
        Route::get('/depots/{depot}', [DepotLedgerController::class, 'show'])
            ->name('depots.show');
        Route::post('/depots/{depot}/charges', [DepotLedgerController::class, 'recordCharge'])
            ->name('depots.charges.store');
        Route::post('/depots/{depot}/payments', [DepotLedgerController::class, 'recordPayment'])
            ->name('depots.payments.store');
        Route::post('/depots/{depot}/monthly-storage', [DepotLedgerController::class, 'runMonthlyStorage'])
            ->name('depots.monthly-storage.run');
        Route::get('/depots/{depot}/monthly-storage/preview', [DepotLedgerController::class, 'previewMonthlyStorage'])
            ->name('depots.monthly-storage.preview');

        // Batch / landed costs
        Route::post('/purchases/{purchase}/batch-costs', [BatchCostController::class, 'store'])
            ->name('purchases.batch-costs.store');
        Route::delete('/purchases/{purchase}/batch-costs/{batchCost}', [BatchCostController::class, 'destroy'])
            ->name('purchases.batch-costs.destroy');

        // Sales (company-scoped)
        Route::get('/sales/export', [SalesController::class, 'exportCsv'])->name('sales.export');
        Route::resource('sales', SalesController::class)->only(['index','store','update']);
        Route::post('sales/{sale}/post', [SalesController::class, 'post'])->name('sales.post');
    
    });
});

/*
|--------------------------------------------------------------------------
| Admin area – only for owners
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'company.setup', 'active.company', 'role:owner'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions.sync');
    });