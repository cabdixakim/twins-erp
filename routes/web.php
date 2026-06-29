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
use App\Http\Controllers\ClientLedgerController;
use App\Http\Controllers\DepotLedgerController;
use App\Http\Controllers\BatchCostController;
use App\Http\Controllers\DepotChargeConfigController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ImportNominationController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountRecoveryController;

/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
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
| Auth routes
|--------------------------------------------------------------------------
*/
Route::middleware('company.setup')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Account Recovery — public
|--------------------------------------------------------------------------
*/
Route::get('/account-recovery', [AccountRecoveryController::class, 'show'])
    ->name('account-recovery');
Route::post('/account-recovery', [AccountRecoveryController::class, 'recover'])
    ->name('account-recovery.recover');

/*
|--------------------------------------------------------------------------
| Protected area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'company.setup', 'user.active'])->group(function () {

    // Profile — any authenticated user
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
    Route::post('/profile/recovery-token', [ProfileController::class, 'generateRecoveryToken'])->name('profile.recovery-token');
    Route::post('/profile/recovery-token/clear', [ProfileController::class, 'clearRecoveryToken'])->name('profile.recovery-token.clear');

    // Switcher — accessible even if no active company
    Route::get('/companies/switcher', [CompanySwitcherController::class, 'index'])
        ->name('companies.switcher');
    Route::get('/companies/{company}/switch', [CompanySwitcherController::class, 'switch'])
        ->name('companies.switch');
    Route::post('/companies', [CompanySwitcherController::class, 'store'])
        ->name('companies.store');

    // Everything else requires an active company
    Route::middleware(['active.company'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        /*
        |------------------------------------------------------------------
        | Depot stock
        |------------------------------------------------------------------
        */
        Route::get('/depot-stock', [DepotStockController::class, 'index'])
            ->middleware('permission:inventory.view')
            ->name('depot-stock.index');

        Route::get('/inventory-adjustments', [\App\Http\Controllers\InventoryAdjustmentController::class, 'index'])
            ->middleware('permission:inventory.view')
            ->name('inventory-adjustments.index');
        Route::get('/inventory-adjustments/create', [\App\Http\Controllers\InventoryAdjustmentController::class, 'create'])
            ->middleware('permission:purchases.receive')
            ->name('inventory-adjustments.create');
        Route::post('/inventory-adjustments', [\App\Http\Controllers\InventoryAdjustmentController::class, 'store'])
            ->middleware('permission:purchases.receive')
            ->name('inventory-adjustments.store');
        Route::get('/depot-stock/available', [DepotStockController::class, 'available'])
            ->middleware('permission:inventory.view')
            ->name('depot-stock.available');
        Route::get('/depot-stock/export', [DepotStockController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('depot-stock.export');

        /*
        |------------------------------------------------------------------
        | Settings
        |------------------------------------------------------------------
        */
        Route::prefix('settings')->name('settings.')->group(function () {

            Route::get('/', fn() => view('settings.hub'))->name('hub');

            Route::get('/full-export', [\App\Http\Controllers\ExportController::class, 'fullDump'])
                ->middleware('permission:reports.export')
                ->name('full-export');

            // Company settings
            Route::get('/company', [CompanySettingsController::class, 'edit'])
                ->middleware('permission:settings.company')
                ->name('company.edit');
            Route::patch('/company', [CompanySettingsController::class, 'update'])
                ->middleware('permission:settings.company')
                ->name('company.update');

            // Depots (settings — create/edit)
            Route::get('/depots', [DepotController::class, 'index'])
                ->middleware('permission:depots.manage')
                ->name('depots.index');
            Route::post('/depots', [DepotController::class, 'store'])
                ->middleware('permission:depots.manage')
                ->name('depots.store');
            Route::patch('/depots/{depot}', [DepotController::class, 'update'])
                ->middleware('permission:depots.manage')
                ->name('depots.update');
            Route::patch('/depots/{depot}/toggle-active', [DepotController::class, 'toggleActive'])
                ->middleware('permission:depots.manage')
                ->name('depots.toggle-active');

            // Suppliers (settings — create/edit)
            Route::get('/suppliers', [SupplierController::class, 'index'])
                ->middleware('permission:suppliers.manage')
                ->name('suppliers.index');
            Route::post('/suppliers', [SupplierController::class, 'store'])
                ->middleware('permission:suppliers.manage')
                ->name('suppliers.store');
            Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update'])
                ->middleware('permission:suppliers.manage')
                ->name('suppliers.update');
            Route::patch('/suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])
                ->middleware('permission:suppliers.manage')
                ->name('suppliers.toggle-active');

            // Transporters (settings — create/edit)
            Route::get('/transporters', [TransporterController::class, 'index'])
                ->middleware('permission:transporters.manage')
                ->name('transporters.index');
            Route::post('/transporters', [TransporterController::class, 'store'])
                ->middleware('permission:transporters.manage')
                ->name('transporters.store');
            Route::patch('/transporters/{transporter}', [TransporterController::class, 'update'])
                ->middleware('permission:transporters.manage')
                ->name('transporters.update');
            Route::patch('/transporters/{transporter}/toggle-active', [TransporterController::class, 'toggleActive'])
                ->middleware('permission:transporters.manage')
                ->name('transporters.toggle-active');

            // Inventory settings
            Route::get('/inventory', [\App\Http\Controllers\Settings\InventorySettingsController::class, 'index'])
                ->middleware('permission:settings.inventory')
                ->name('inventory.index');
            Route::patch('/inventory/costing', [\App\Http\Controllers\Settings\InventorySettingsController::class, 'updateCosting'])
                ->middleware('permission:settings.inventory')
                ->name('inventory.update-costing');
            Route::post('/inventory/close-period', [\App\Http\Controllers\Settings\InventorySettingsController::class, 'closePeriod'])
                ->middleware('permission:settings.inventory')
                ->name('inventory.close-period');

            // Duty vendors (customs authorities)
            Route::get('/duty-vendors', [\App\Http\Controllers\Settings\DutyVendorController::class, 'index'])
                ->middleware('permission:settings.company')
                ->name('duty-vendors.index');
            Route::post('/duty-vendors', [\App\Http\Controllers\Settings\DutyVendorController::class, 'store'])
                ->middleware('permission:settings.company')
                ->name('duty-vendors.store');
            Route::patch('/duty-vendors/{dutyVendor}', [\App\Http\Controllers\Settings\DutyVendorController::class, 'update'])
                ->middleware('permission:settings.company')
                ->name('duty-vendors.update');
            Route::patch('/duty-vendors/{dutyVendor}/toggle', [\App\Http\Controllers\Settings\DutyVendorController::class, 'toggleActive'])
                ->middleware('permission:settings.company')
                ->name('duty-vendors.toggle');

            // Duty rates
            Route::get('/duty-rates', [\App\Http\Controllers\Settings\DutyRateController::class, 'index'])
                ->middleware('permission:settings.company')
                ->name('duty-rates.index');
            Route::post('/duty-rates', [\App\Http\Controllers\Settings\DutyRateController::class, 'store'])
                ->middleware('permission:settings.company')
                ->name('duty-rates.store');
            Route::patch('/duty-rates/{dutyRate}', [\App\Http\Controllers\Settings\DutyRateController::class, 'update'])
                ->middleware('permission:settings.company')
                ->name('duty-rates.update');
            Route::delete('/duty-rates/{dutyRate}', [\App\Http\Controllers\Settings\DutyRateController::class, 'destroy'])
                ->middleware('permission:settings.company')
                ->name('duty-rates.destroy');

            // Clients (settings — create/edit master records)
            Route::get('/clients', [ClientController::class, 'index'])
                ->middleware('permission:clients.view')
                ->name('clients.index');
            Route::get('/clients/export', [ClientController::class, 'exportCsv'])
                ->middleware('permission:reports.export')
                ->name('clients.export');
            Route::post('/clients', [ClientController::class, 'store'])
                ->middleware('permission:clients.create')
                ->name('clients.store');
            Route::patch('/clients/{client}', [ClientController::class, 'update'])
                ->middleware('permission:clients.edit')
                ->name('clients.update');
            Route::patch('/clients/{client}/toggle-active', [ClientController::class, 'toggleActive'])
                ->middleware('permission:clients.edit')
                ->name('clients.toggle-active');
        });

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

        /*
        |------------------------------------------------------------------
        | Products
        |------------------------------------------------------------------
        */
        Route::get('/products', [ProductController::class, 'index'])
            ->middleware('permission:settings.products')
            ->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])
            ->middleware('permission:settings.products')
            ->name('products.store');
        Route::patch('/products/{product}', [ProductController::class, 'update'])
            ->middleware('permission:settings.products')
            ->name('products.update');
        Route::patch('/products/{product}/toggle-active', [ProductController::class, 'toggleActive'])
            ->middleware('permission:settings.products')
            ->name('products.toggle-active');

        // Clearances — view (any auth)
        Route::get('/clearances', [\App\Http\Controllers\ClearanceController::class, 'index'])
            ->middleware('permission:purchases.view')
            ->name('clearances.index');

        /*
        |------------------------------------------------------------------
        | Purchases
        |------------------------------------------------------------------
        */
        Route::get('/purchases', [PurchaseController::class, 'index'])
            ->middleware('permission:purchases.view')
            ->name('purchases.index');
        Route::get('/purchases/export', [PurchaseController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('purchases.export');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])
            ->middleware('permission:purchases.create')
            ->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])
            ->middleware('permission:purchases.create')
            ->name('purchases.store');
        Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])
            ->middleware('permission:purchases.view')
            ->name('purchases.show');
        Route::post('/purchases/{purchase}/confirm', [PurchaseController::class, 'confirm'])
            ->middleware('permission:purchases.confirm')
            ->name('purchases.confirm');
        Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])
            ->middleware('permission:purchases.receive')
            ->name('purchases.receive');
        Route::post('/purchases/{purchase}/undo-receipt', [PurchaseController::class, 'undoReceipt'])
            ->middleware('permission:purchases.undo-receipt')
            ->name('purchases.undo-receipt');
        Route::post('/purchases/{purchase}/cross-dock-transfer', [PurchaseController::class, 'crossDockTransfer'])
            ->middleware('permission:purchases.cross-dock-transfer')
            ->name('purchases.cross-dock-transfer');
        Route::post('/purchases/{purchase}/cross-dock-dispatch', [PurchaseController::class, 'crossDockDispatch'])
            ->middleware('permission:purchases.cross-dock-dispatch')
            ->name('purchases.cross-dock-dispatch');
        Route::post('/purchases/{purchase}/nominate', [PurchaseController::class, 'nominate'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.nominate');
        Route::post('/purchases/{purchase}/import-deliver', [PurchaseController::class, 'importDeliver'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-deliver');
        Route::get('/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])
            ->middleware('permission:purchases.create')
            ->name('purchases.edit');
        Route::patch('/purchases/{purchase}', [PurchaseController::class, 'update'])
            ->middleware('permission:purchases.create')
            ->name('purchases.update');
        Route::post('/purchases/{purchase}/shipper-credit-note', [PurchaseController::class, 'shipperCreditNote'])
            ->middleware('permission:purchases.receive')
            ->name('purchases.shipper-credit-note');
        Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])
            ->middleware('permission:purchases.cancel')
            ->name('purchases.cancel');
        Route::post('/purchases/{purchase}/void', [PurchaseController::class, 'void'])
            ->middleware('permission:purchases.void')
            ->name('purchases.void');

        /*
        |------------------------------------------------------------------
        | Import logistics (nominations + truck lifecycle)
        |------------------------------------------------------------------
        */
        Route::post('/purchases/{purchase}/import-nomination',
            [ImportNominationController::class, 'store'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.store');

        Route::patch('/purchases/{purchase}/import-nomination/{nomination}',
            [ImportNominationController::class, 'update'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.update');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks',
            [ImportNominationController::class, 'addTruck'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.store');

        Route::patch('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}',
            [ImportNominationController::class, 'updateTruck'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.update');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/record-load',
            [ImportNominationController::class, 'recordLoad'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.record-load');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/fail-load',
            [ImportNominationController::class, 'failLoad'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.fail-load');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/mark-in-transit',
            [ImportNominationController::class, 'markInTransit'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.mark-in-transit');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/mark-at-border',
            [ImportNominationController::class, 'markAtBorder'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.mark-at-border');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/record-border',
            [ImportNominationController::class, 'recordBorder'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.record-border');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/record-delivery',
            [ImportNominationController::class, 'recordDelivery'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.record-delivery');

        Route::get('/purchases/{purchase}/import-nomination/{nomination}/truck-template',
            [ImportNominationController::class, 'truckTemplate'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.template');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/import-trucks',
            [ImportNominationController::class, 'importTrucks'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.import');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/{truck}/quick-load-deliver',
            [ImportNominationController::class, 'quickLoadDeliver'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.quick-load-deliver');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/bulk-in-transit',
            [ImportNominationController::class, 'bulkMarkInTransit'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.bulk-in-transit');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/bulk-border-cleared',
            [ImportNominationController::class, 'bulkMarkBorderCleared'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.bulk-border-cleared');

        Route::post('/purchases/{purchase}/import-nomination/{nomination}/trucks/bulk-quick-post',
            [ImportNominationController::class, 'bulkQuickPost'])
            ->middleware('permission:purchases.import-nominations')
            ->name('purchases.import-nomination.trucks.bulk-quick-post');

        /*
        |------------------------------------------------------------------
        | Transporter ledger
        |------------------------------------------------------------------
        */
        Route::get('/transporters', [TransporterLedgerController::class, 'index'])
            ->middleware('permission:transporters.view')
            ->name('transporters.index');
        Route::get('/transporters/{transporter}/statement', [TransporterLedgerController::class, 'statement'])
            ->middleware('permission:transporters.view')
            ->name('transporters.statement');
        Route::get('/transporters/{transporter}/export', [TransporterLedgerController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('transporters.export');
        Route::get('/transporters/{transporter}', [TransporterLedgerController::class, 'show'])
            ->middleware('permission:transporters.view')
            ->name('transporters.show');
        Route::post('/transporters/{transporter}/payments', [TransporterLedgerController::class, 'recordPayment'])
            ->middleware('permission:transporters.payments')
            ->name('transporters.payments.store');
        Route::post('/transporters/{transporter}/advances', [TransporterLedgerController::class, 'recordAdvance'])
            ->middleware('permission:transporters.charges')
            ->name('transporters.advances.store');
        Route::post('/transporters/{transporter}/adjustments', [TransporterLedgerController::class, 'recordAdjustment'])
            ->middleware('permission:transporters.charges')
            ->name('transporters.adjustments.store');
        Route::post('/transporters/{transporter}/settle', [TransporterLedgerController::class, 'settle'])
            ->middleware('permission:transporters.payments')
            ->name('transporters.settle');

        /*
        |------------------------------------------------------------------
        | Supplier ledger
        |------------------------------------------------------------------
        */
        Route::get('/suppliers', [SupplierLedgerController::class, 'index'])
            ->middleware('permission:suppliers.view')
            ->name('suppliers.index');
        Route::get('/suppliers/{supplier}/statement', [SupplierLedgerController::class, 'statement'])
            ->middleware('permission:suppliers.view')
            ->name('suppliers.statement');
        Route::get('/suppliers/{supplier}/export', [SupplierLedgerController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('suppliers.export');
        Route::get('/suppliers/{supplier}', [SupplierLedgerController::class, 'show'])
            ->middleware('permission:suppliers.view')
            ->name('suppliers.show');
        Route::post('/suppliers/{supplier}/payments', [SupplierLedgerController::class, 'recordPayment'])
            ->middleware('permission:suppliers.payments')
            ->name('suppliers.payments.store');
        Route::post('/suppliers/{supplier}/credits', [SupplierLedgerController::class, 'recordCredit'])
            ->middleware('permission:suppliers.credits')
            ->name('suppliers.credits.store');

        /*
        |------------------------------------------------------------------
        | Client AR ledger
        |------------------------------------------------------------------
        */
        Route::get('/clients', [ClientLedgerController::class, 'index'])
            ->middleware('permission:clients.view')
            ->name('clients.index');
        Route::get('/clients/{client}/statement', [ClientLedgerController::class, 'statement'])
            ->middleware('permission:clients.view')
            ->name('clients.statement');
        Route::get('/clients/{client}/export', [ClientLedgerController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('clients.export');
        Route::get('/clients/{client}', [ClientLedgerController::class, 'show'])
            ->middleware('permission:clients.view')
            ->name('clients.show');
        Route::post('/clients/{client}/payments', [ClientLedgerController::class, 'recordPayment'])
            ->middleware('permission:clients.edit')
            ->name('clients.payments.store');
        Route::post('/clients/{client}/credits', [ClientLedgerController::class, 'recordCredit'])
            ->middleware('permission:clients.edit')
            ->name('clients.credits.store');
        Route::post('/clients/{client}/adjustments', [ClientLedgerController::class, 'recordAdjustment'])
            ->middleware('permission:clients.edit')
            ->name('clients.adjustments.store');

        /*
        |------------------------------------------------------------------
        | Depot charge configs (rate cards)
        |------------------------------------------------------------------
        */
        Route::post('/depots/{depot}/charge-configs', [DepotChargeConfigController::class, 'store'])
            ->middleware('permission:depots.charges')
            ->name('depots.charge-configs.store');
        Route::patch('/depots/{depot}/charge-configs/{config}', [DepotChargeConfigController::class, 'update'])
            ->middleware('permission:depots.charges')
            ->name('depots.charge-configs.update');
        Route::delete('/depots/{depot}/charge-configs/{config}', [DepotChargeConfigController::class, 'destroy'])
            ->middleware('permission:depots.charges')
            ->name('depots.charge-configs.destroy');
        Route::patch('/depots/{depot}/charge-configs/{config}/toggle', [DepotChargeConfigController::class, 'toggleActive'])
            ->middleware('permission:depots.charges')
            ->name('depots.charge-configs.toggle');

        /*
        |------------------------------------------------------------------
        | Depot ledger
        |------------------------------------------------------------------
        */
        Route::get('/depots', [DepotLedgerController::class, 'index'])
            ->middleware('permission:depots.view')
            ->name('depots.index');
        Route::get('/depots/{depot}/statement', [DepotLedgerController::class, 'statement'])
            ->middleware('permission:depots.view')
            ->name('depots.statement');
        Route::get('/depots/{depot}/export', [DepotLedgerController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('depots.export');
        Route::get('/depots/{depot}', [DepotLedgerController::class, 'show'])
            ->middleware('permission:depots.view')
            ->name('depots.show');
        Route::post('/depots/{depot}/charges', [DepotLedgerController::class, 'recordCharge'])
            ->middleware('permission:depots.charges')
            ->name('depots.charges.store');
        Route::post('/depots/{depot}/payments', [DepotLedgerController::class, 'recordPayment'])
            ->middleware('permission:depots.payments')
            ->name('depots.payments.store');
        Route::post('/depots/{depot}/monthly-storage', [DepotLedgerController::class, 'runMonthlyStorage'])
            ->middleware('permission:depots.charges')
            ->name('depots.monthly-storage.run');
        Route::get('/depots/{depot}/monthly-storage/preview', [DepotLedgerController::class, 'previewMonthlyStorage'])
            ->middleware('permission:depots.view')
            ->name('depots.monthly-storage.preview');

        /*
        |------------------------------------------------------------------
        | Batch / landed costs
        |------------------------------------------------------------------
        */
        Route::post('/purchases/{purchase}/batch-costs', [BatchCostController::class, 'store'])
            ->middleware('permission:purchases.batch-costs')
            ->name('purchases.batch-costs.store');
        Route::delete('/purchases/{purchase}/batch-costs/{batchCost}', [BatchCostController::class, 'destroy'])
            ->middleware('permission:purchases.batch-costs')
            ->name('purchases.batch-costs.destroy');

        // Hospitality charges
        Route::post('/purchases/{purchase}/hospitality', [\App\Http\Controllers\HospitalityController::class, 'store'])
            ->middleware('permission:purchases.batch-costs')
            ->name('purchases.hospitality.store');
        Route::delete('/purchases/{purchase}/hospitality/{hospitalityCharge}', [\App\Http\Controllers\HospitalityController::class, 'destroy'])
            ->middleware('permission:purchases.batch-costs')
            ->name('purchases.hospitality.destroy');

        /*
        |------------------------------------------------------------------
        | Duties
        |------------------------------------------------------------------
        */
        Route::get('/duties', [\App\Http\Controllers\DutiesController::class, 'index'])
            ->middleware('permission:purchases.view')
            ->name('duties.index');
        Route::get('/duties/export', [\App\Http\Controllers\DutiesController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('duties.export');

        // Duty rate lookup (AJAX — used in purchase forms)
        Route::get('/duty-rates/for-product', [\App\Http\Controllers\Settings\DutyRateController::class, 'forProduct'])
            ->middleware('permission:purchases.view')
            ->name('settings.duty-rates.for-product');

        /*
        |------------------------------------------------------------------
        | Duty vendor ledger (customs authorities AP)
        |------------------------------------------------------------------
        */
        Route::get('/duty-vendors', [\App\Http\Controllers\DutyLedgerController::class, 'index'])
            ->middleware('permission:suppliers.view')
            ->name('duty-vendors.index');
        Route::get('/duty-vendors/{dutyVendor}/statement', [\App\Http\Controllers\DutyLedgerController::class, 'statement'])
            ->middleware('permission:suppliers.view')
            ->name('duty-vendors.statement');
        Route::get('/duty-vendors/{dutyVendor}/export', [\App\Http\Controllers\DutyLedgerController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('duty-vendors.export');
        Route::get('/duty-vendors/{dutyVendor}', [\App\Http\Controllers\DutyLedgerController::class, 'show'])
            ->middleware('permission:suppliers.view')
            ->name('duty-vendors.show');
        Route::post('/duty-vendors/{dutyVendor}/payments', [\App\Http\Controllers\DutyLedgerController::class, 'recordPayment'])
            ->middleware('permission:suppliers.payments')
            ->name('duty-vendors.payments.store');
        Route::post('/duty-vendors/{dutyVendor}/adjustments', [\App\Http\Controllers\DutyLedgerController::class, 'recordAdjustment'])
            ->middleware('permission:suppliers.payments')
            ->name('duty-vendors.adjustments.store');

        /*
        |------------------------------------------------------------------
        | Sales
        |------------------------------------------------------------------
        */
        Route::get('/sales/export', [SalesController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('sales.export');
        Route::get('/sales', [SalesController::class, 'index'])
            ->middleware('permission:sales.view')
            ->name('sales.index');
        Route::post('/sales', [SalesController::class, 'store'])
            ->middleware('permission:sales.create')
            ->name('sales.store');
        Route::put('/sales/{sale}', [SalesController::class, 'update'])
            ->middleware('permission:sales.edit')
            ->name('sales.update');
        Route::post('/sales/{sale}/post', [SalesController::class, 'post'])
            ->middleware('permission:sales.post')
            ->name('sales.post');
        Route::get('/sales/{sale}/delivery-note', [SalesController::class, 'deliveryNote'])
            ->middleware('permission:sales.view')
            ->name('sales.delivery-note');
        Route::post('/sales/{sale}/pod', [SalesController::class, 'confirmPod'])
            ->middleware('permission:sales.edit')
            ->name('sales.pod');
        Route::post('/sales/{sale}/cancel', [SalesController::class, 'cancelSale'])
            ->middleware('permission:sales.edit')
            ->name('sales.cancel');

        /*
        |------------------------------------------------------------------
        | Invoices
        |------------------------------------------------------------------
        */
        Route::get('/invoices', [InvoiceController::class, 'index'])
            ->middleware('permission:sales.view')
            ->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])
            ->middleware('permission:sales.view')
            ->name('invoices.show');
        Route::patch('/invoices/{invoice}/notes', [InvoiceController::class, 'updateNotes'])
            ->middleware('permission:sales.edit')
            ->name('invoices.update-notes');
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])
            ->middleware('permission:sales.post')
            ->name('invoices.mark-paid');
        Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])
            ->middleware('permission:purchases.void')
            ->name('invoices.void');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
            ->middleware('permission:sales.view')
            ->name('invoices.pdf');
        Route::post('/invoices/{invoice}/credit-note', [InvoiceController::class, 'creditNote'])
            ->middleware('permission:sales.edit')
            ->name('invoices.credit-note');

    });
});

/*
|--------------------------------------------------------------------------
| Petty Cash
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'company.setup', 'active.company', 'user.active'])
    ->prefix('petty-cash')
    ->name('petty-cash.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\PettyCashController::class, 'index'])
            ->middleware('permission:petty-cash.view')
            ->name('index');
        Route::post('/accounts', [\App\Http\Controllers\PettyCashController::class, 'storeAccount'])
            ->middleware('permission:petty-cash.manage')
            ->name('store');
        Route::post('/accounts/{account}/transactions', [\App\Http\Controllers\PettyCashController::class, 'recordTransaction'])
            ->middleware('permission:petty-cash.transact')
            ->name('transaction');
        Route::post('/accounts/{account}/transactions/{transaction}/void', [\App\Http\Controllers\PettyCashController::class, 'voidTransaction'])
            ->middleware('permission:petty-cash.manage')
            ->name('transaction.void');
        Route::post('/accounts/{account}/toggle', [\App\Http\Controllers\PettyCashController::class, 'toggleAccount'])
            ->middleware('permission:petty-cash.manage')
            ->name('toggle');
        Route::get('/accounts/{account}/export', [\App\Http\Controllers\PettyCashController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('export');
    });

/*
|--------------------------------------------------------------------------
| Banks
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'company.setup', 'active.company', 'user.active'])
    ->prefix('banks')
    ->name('banks.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\BankAccountController::class, 'index'])
            ->middleware('permission:petty-cash.view')
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\BankAccountController::class, 'create'])
            ->middleware('permission:petty-cash.manage')
            ->name('create');
        Route::post('/', [\App\Http\Controllers\BankAccountController::class, 'store'])
            ->middleware('permission:petty-cash.manage')
            ->name('store');
        Route::get('/{bank}', [\App\Http\Controllers\BankAccountController::class, 'show'])
            ->middleware('permission:petty-cash.view')
            ->name('show');
        Route::get('/{bank}/edit', [\App\Http\Controllers\BankAccountController::class, 'edit'])
            ->middleware('permission:petty-cash.manage')
            ->name('edit');
        Route::patch('/{bank}', [\App\Http\Controllers\BankAccountController::class, 'update'])
            ->middleware('permission:petty-cash.manage')
            ->name('update');
        Route::post('/{bank}/toggle-active', [\App\Http\Controllers\BankAccountController::class, 'toggleActive'])
            ->middleware('permission:petty-cash.manage')
            ->name('toggle-active');
        Route::post('/{bank}/transactions', [\App\Http\Controllers\BankAccountController::class, 'recordTransaction'])
            ->middleware('permission:petty-cash.transact')
            ->name('transactions.store');
        Route::post('/{bank}/transactions/{transaction}/void', [\App\Http\Controllers\BankAccountController::class, 'voidTransaction'])
            ->middleware('permission:petty-cash.manage')
            ->name('transactions.void');
        Route::post('/{bank}/reconcile', [\App\Http\Controllers\BankAccountController::class, 'reconcile'])
            ->middleware('permission:petty-cash.manage')
            ->name('reconcile');
        Route::get('/{bank}/export', [\App\Http\Controllers\BankAccountController::class, 'exportCsv'])
            ->middleware('permission:reports.export')
            ->name('export');
    });

/*
|--------------------------------------------------------------------------
| Reports
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'company.setup', 'active.company', 'user.active'])
    ->prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])
            ->middleware('permission:reports.export')
            ->name('index');
        Route::get('/pl', [\App\Http\Controllers\ReportController::class, 'profitAndLoss'])
            ->middleware('permission:reports.export')
            ->name('pl');
        Route::get('/ar-aging', [\App\Http\Controllers\ReportController::class, 'arAging'])
            ->middleware('permission:reports.export')
            ->name('ar-aging');
        Route::get('/ap-aging', [\App\Http\Controllers\ReportController::class, 'apAging'])
            ->middleware('permission:reports.export')
            ->name('ap-aging');
        Route::get('/throughput', [\App\Http\Controllers\ReportController::class, 'throughput'])
            ->middleware('permission:reports.export')
            ->name('throughput');
    });

/*
|--------------------------------------------------------------------------
| Accounting
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'company.setup', 'active.company', 'user.active'])
    ->prefix('accounting')
    ->name('accounting.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\AccountingController::class, 'index'])
            ->middleware('permission:reports.export')
            ->name('index');
        Route::get('/chart-of-accounts', [\App\Http\Controllers\AccountingController::class, 'chartOfAccounts'])
            ->middleware('permission:reports.export')
            ->name('coa');
        Route::post('/chart-of-accounts', [\App\Http\Controllers\AccountingController::class, 'storeAccount'])
            ->middleware('permission:settings.inventory')
            ->name('coa.store');
        Route::patch('/chart-of-accounts/{account}', [\App\Http\Controllers\AccountingController::class, 'updateAccount'])
            ->middleware('permission:settings.inventory')
            ->name('coa.update');
        Route::delete('/chart-of-accounts/{account}', [\App\Http\Controllers\AccountingController::class, 'destroyAccount'])
            ->middleware('permission:settings.inventory')
            ->name('coa.destroy');
        Route::post('/chart-of-accounts/seed', [\App\Http\Controllers\AccountingController::class, 'seedAccounts'])
            ->middleware('permission:settings.inventory')
            ->name('coa.seed');
        Route::get('/pl', [\App\Http\Controllers\AccountingController::class, 'pl'])
            ->middleware('permission:reports.export')
            ->name('pl');
        Route::get('/balance-sheet', [\App\Http\Controllers\AccountingController::class, 'balanceSheet'])
            ->middleware('permission:reports.export')
            ->name('balance-sheet');
        Route::get('/journals', [\App\Http\Controllers\AccountingController::class, 'journals'])
            ->middleware('permission:reports.export')
            ->name('journals');
        Route::post('/journals', [\App\Http\Controllers\AccountingController::class, 'storeJournal'])
            ->middleware('permission:settings.inventory')
            ->name('journals.store');
        Route::get('/trial-balance', [\App\Http\Controllers\AccountingController::class, 'trialBalance'])
            ->middleware('permission:reports.export')
            ->name('trial-balance');
    });

/*
|--------------------------------------------------------------------------
| Admin area — owners and admins only
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'company.setup', 'active.company', 'user.active', 'permission:admin.users'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // User management
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Roles management
        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:admin.roles')
            ->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:admin.roles')
            ->name('roles.store');
        Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:admin.roles')
            ->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:admin.roles')
            ->name('roles.destroy');
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->middleware('permission:admin.roles')
            ->name('roles.permissions.sync');

    });

/*
|--------------------------------------------------------------------------
| Documents & Alerts — any authenticated user
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'company.setup', 'active.company', 'user.active'])
    ->group(function () {
        Route::get('/documents', [\App\Http\Controllers\DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/create', [\App\Http\Controllers\DocumentController::class, 'create'])->name('documents.create');
        Route::post('/documents', [\App\Http\Controllers\DocumentController::class, 'store'])
            ->middleware('permission:settings.company')
            ->name('documents.store');
        Route::get('/documents/{document}/download', [\App\Http\Controllers\DocumentController::class, 'download'])->name('documents.download');
        Route::delete('/documents/{document}', [\App\Http\Controllers\DocumentController::class, 'destroy'])
            ->middleware('permission:settings.company')
            ->name('documents.destroy');

        Route::get('/alerts', [\App\Http\Controllers\AlertController::class, 'index'])->name('alerts.index');
        Route::post('/alerts/mark-seen', [\App\Http\Controllers\AlertController::class, 'markSeen'])->name('alerts.mark-seen');

        // Audit log — managers and above (anyone with purchases.view)
        Route::get('/admin/audit-log', [AuditLogController::class, 'index'])
            ->middleware('permission:purchases.view')
            ->name('admin.audit-log');
    });
