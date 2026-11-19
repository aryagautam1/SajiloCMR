<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\ClientExportController;
use App\Http\Controllers\Web\ClientPageController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProjectExportController;
use App\Http\Controllers\Web\ProjectPageController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\UserPageController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/clients', [ClientPageController::class, 'index'])->name('clients.index');
    Route::get('/clients/export', ClientExportController::class)->name('clients.export');

    Route::get('/projects', [ProjectPageController::class, 'index'])->name('projects.index');
    Route::get('/projects/export', ProjectExportController::class)->name('projects.export');
    Route::get('/projects/{project}', [ProjectPageController::class, 'show'])->name('projects.show');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');

    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserPageController::class, 'index'])->name('users.index');
    });
});
