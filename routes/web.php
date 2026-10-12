<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorityController;
use App\Http\Controllers\FarmerController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicController::class, 'index'])->name('public.home');
Route::get('/prices', [PublicController::class, 'prices'])->name('public.prices');
Route::get('/traceability', [PublicController::class, 'traceability'])->name('public.traceability');
Route::get('/traceability/{batch_code}', [PublicController::class, 'traceabilityShow'])->name('public.traceability.show');
Route::get('/report', [PublicController::class, 'reportForm'])->name('public.report');
Route::post('/report', [PublicController::class, 'reportStore'])->name('public.report.store');
Route::get('/report/track', [PublicController::class, 'trackReport'])->name('public.report.track');
Route::get('/faq', [PublicController::class, 'faq'])->name('public.faq');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/demo-login/{role}', [AuthController::class, 'demoLogin'])->name('demo.login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Farmer Workspace
|--------------------------------------------------------------------------
*/
Route::middleware(['role:farmer'])->prefix('farmer')->name('farmer.')->group(function () {
    Route::get('/dashboard', [FarmerController::class, 'dashboard'])->name('dashboard');
    Route::post('/batches', [FarmerController::class, 'storeBatch'])->name('batches.store');
});

/*
|--------------------------------------------------------------------------
| Vendor Workspace (Collector, Wholesale, Retail)
|--------------------------------------------------------------------------
*/
Route::middleware(['role:collector,wholesale,retail'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/dashboard', [VendorController::class, 'dashboard'])->name('dashboard');
    Route::post('/handoff', [VendorController::class, 'storeHandoff'])->name('handoff.store');
    Route::post('/transactions/{id}/void', [VendorController::class, 'voidTransaction'])->name('transactions.void');
});

/*
|--------------------------------------------------------------------------
| Authority Workspace (Market Monitor, Enforcement)
|--------------------------------------------------------------------------
*/
Route::middleware(['role:authority'])->prefix('authority')->name('authority.')->group(function () {
    Route::get('/dashboard', [AuthorityController::class, 'dashboard'])->name('dashboard');
    Route::post('/flags/{id}/review', [AuthorityController::class, 'reviewFlag'])->name('flags.review');
    Route::post('/reports/{id}/moderate', [AuthorityController::class, 'moderateReport'])->name('reports.moderate');
});

/*
|--------------------------------------------------------------------------
| Admin Workspace
|--------------------------------------------------------------------------
*/
Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/users/{id}/toggle', [AdminController::class, 'toggleUserStatus'])->name('users.toggle');
    Route::post('/rules', [AdminController::class, 'storeRule'])->name('rules.store');
    Route::post('/rules/{id}/toggle', [AdminController::class, 'toggleRule'])->name('rules.toggle');
    Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
    Route::get('/batches/verification', [AdminController::class, 'batchVerificationIndex'])->name('admin.batches.verification');
    Route::get('/batches/{batchId}', [AdminController::class, 'batchDetail'])->name('admin.batches.detail');
    Route::post('/batches/{batchId}/verify', [AdminController::class, 'verifyBatch'])->name('admin.batches.verify');
    Route::post('/batches/{batchId}/reject', [AdminController::class, 'rejectBatch'])->name('admin.batches.reject');
    Route::get('/batches/verification', [AdminController::class, 'batchVerificationIndex'])->name('admin.batches.verification');
    Route::get('/batches/{batchId}', [AdminController::class, 'batchDetail'])->name('admin.batches.detail');
    Route::post('/batches/{batchId}/verify', [AdminController::class, 'verifyBatch'])->name('admin.batches.verify');
    Route::post('/batches/{batchId}/reject', [AdminController::class, 'rejectBatch'])->name('admin.batches.reject');
    Route::get('/batches/verification', [AdminController::class, 'batchVerificationIndex'])->name('admin.batches.verification');
    Route::get('/batches/{batchId}', [AdminController::class, 'batchDetail'])->name('admin.batches.detail');
    Route::post('/batches/{batchId}/verify', [AdminController::class, 'verifyBatch'])->name('admin.batches.verify');
    Route::post('/batches/{batchId}/reject', [AdminController::class, 'rejectBatch'])->name('admin.batches.reject');
});
