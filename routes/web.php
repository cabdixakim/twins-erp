<?php

use Illuminate\Support\Facades\Route;

// Controllers that match your current folder structure
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Onboarding\CompanyController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Settings\DepotController;
use App\Http\Controllers\Settings\CompanySettingsController;
use App\Http\Controllers\Settings\SupplierController;
use App\Http\Controllers\Settings\TransporterController;
use App\Http\Controllers\DepotStock\DepotStockController;

// ---------------------------------------------------------------------
// Home → send people to login or dashboard
// ---------------------------------------------------------------------
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// ---------------------------------------------------------------------
// Onboarding – public for first-time setup
// ---------------------------------------------------------------------
Route::get('/company/create', [CompanyController::class, 'create'])
    ->name('company.create');

Route::post('/company', [CompanyController::class, 'store'])
    ->name('company.store');

// ---------------------------------------------------------------------
// Auth routes
// ---------------------------------------------------------------------
// Show login page
Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

// Handle login POST
Route::post('/login', [AuthController::class, 'login'])
    ->name('login.post');

// ---------------------------------------------------------------------
// Protected area
// ---------------------------------------------------------------------
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    // ----------------------- DEPOT STOCK ------------------------------
    Route::get('/depot-stock', [DepotStockController::class, 'index'])
        ->name('depot-stock.index');

    // Logout current user
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // ------------------------- SETTINGS ------------------------------
    Route::prefix('settings')
        ->name('settings.')
        ->group(function () {

            // Company profile (NEW)
            Route::get('/company', [CompanySettingsController::class, 'edit'])
                ->name('company.edit');
            Route::patch('/company', [CompanySettingsController::class, 'update'])
                ->name('company.update');

            // Depots
            Route::get('/depots', [DepotController::class, 'index'])
                ->name('depots.index');
            Route::post('/depots', [DepotController::class, 'store'])
                ->name('depots.store');
            Route::patch('/depots/{depot}', [DepotController::class, 'update'])
                ->name('depots.update');
            Route::patch('/depots/{depot}/toggle-active', [DepotController::class, 'toggleActive'])
                ->name('depots.toggle-active');
                // Settings: Suppliers
        Route::get('/suppliers', [SupplierController::class, 'index'])
            ->name('suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])
            ->name('suppliers.store');
        Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update'])
            ->name('suppliers.update');
        Route::patch('/suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])
            ->name('suppliers.toggle-active');

            // Settings: Transporters
        Route::get('/transporters', [TransporterController::class, 'index'])
            ->name('transporters.index');
        Route::post('/transporters', [TransporterController::class, 'store'])
            ->name('transporters.store');
        Route::patch('/transporters/{transporter}', [TransporterController::class, 'update'])
            ->name('transporters.update');
        Route::patch('/transporters/{transporter}/toggle-active', [TransporterController::class, 'toggleActive'])
            ->name('transporters.toggle-active');

     });
        
});

// ---------------------------------------------------------------------
// Admin area – only for owners
// ---------------------------------------------------------------------
Route::middleware(['auth', 'role:owner'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Users
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');
        Route::post('/users', [UserController::class, 'store'])
            ->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])
            ->name('users.update');
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->name('users.reset-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->name('users.destroy');

        // Roles & permissions
        Route::get('/roles', [RoleController::class, 'index'])
            ->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])
            ->name('roles.store');
        Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])
            ->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->name('roles.destroy');
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->name('roles.permissions.sync');
    });