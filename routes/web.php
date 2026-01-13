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

        Route::prefix('settings')->name('settings.')->group(function () {

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
        });

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');
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