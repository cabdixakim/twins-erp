<?php

use Illuminate\Support\Facades\Route;

// NOTE: these match your actual folder structure:
// app/Http/Controllers/DashboardController.php
// app/Http/Controllers/AuthController.php
// app/Http/Controllers/Onboarding/CompanyController.php
// app/Http/Controllers/Admin/RoleController.php
// app/Http/Controllers/Admin/UserController.php
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Onboarding\CompanyController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;

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

    // Logout current user
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
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

        // create role
        Route::post('/roles', [RoleController::class, 'store'])
            ->name('roles.store');

        // update role (supports PUT or PATCH)
        Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])
            ->name('roles.update');

        // delete role
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->name('roles.destroy');

        // sync permissions for a role (used by the Blade UI)
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->name('roles.permissions.sync');
    });