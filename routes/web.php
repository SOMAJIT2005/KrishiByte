<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorityController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\FarmerAccessController;
use App\Http\Controllers\FarmerController;
use App\Http\Controllers\FarmerProfileController;
use App\Http\Controllers\HandoffController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicController::class, 'index'])->name('public.home');
Route::get('/prices', [PublicController::class, 'prices'])->name('public.prices');
Route::get('/analytics', [PublicController::class, 'analytics'])->middleware('throttle:analytics')->name('public.analytics');
Route::get('/farmers', [FarmerProfileController::class, 'index'])->name('farmers.index');
Route::get('/farmers/enroll', [FarmerProfileController::class, 'enrollForm'])->name('farmers.enroll');
Route::post('/farmers/enroll', [FarmerProfileController::class, 'enroll'])->middleware('throttle:enrollment')->name('farmers.enroll.store');
Route::get('/farmers/{id}', [FarmerProfileController::class, 'show'])->name('farmers.show');
Route::post('/farmers/{id}/ratings', [FarmerProfileController::class, 'rate'])->middleware(['role:consumer,collector,wholesale,retail', 'throttle:ratings'])->name('farmers.rate');
Route::middleware('role:collector')->group(function () {
    Route::get('/vendor/farmer-purchase', [FarmerProfileController::class, 'purchaseForm'])->name('vendor.farm-purchase');
    Route::post('/vendor/farmer-purchase', [FarmerProfileController::class, 'purchase'])->middleware('throttle:purchases')->name('vendor.farm-purchase.store');
});
Route::get('/farmer-agreements/{id}', [FarmerProfileController::class, 'agreement'])->name('farmers.agreement');
Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::get('/farmer-review', [FarmerProfileController::class, 'reviewQueue'])->name('admin.farmer-review');
    Route::post('/farmer-enrollments/{id}', [FarmerProfileController::class, 'reviewEnrollment'])->middleware('password.confirm')->name('admin.farmer-enrollment.review');
    Route::post('/farmer-ratings/{id}', [FarmerProfileController::class, 'moderateRating'])->middleware('password.confirm')->name('admin.farmer-rating.review');
});
Route::get('/traceability', [PublicController::class, 'traceability'])->name('public.traceability');
Route::get('/traceability/{batch_code}', [PublicController::class, 'traceabilityShow'])->name('public.traceability.show');
Route::get('/report', [PublicController::class, 'reportForm'])->name('public.report');
Route::post('/report', [PublicController::class, 'reportStore'])->middleware('throttle:reports')->name('public.report.store');
Route::middleware('role:admin,authority,farmer,collector,wholesale,retail,consumer')->group(function () {
    Route::get('/my-reports', [PublicController::class, 'myReports'])->name('reports.mine');
    Route::get('/reports/{id}/evidence', [PublicController::class, 'evidence'])->name('reports.evidence');
});
Route::get('/report/track', [PublicController::class, 'trackReport'])->middleware('throttle:tracking')->name('public.report.track');
Route::get('/faq', [PublicController::class, 'faq'])->name('public.faq');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:registration')->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Farmer Workspace
|--------------------------------------------------------------------------
*/
Route::middleware(['role:farmer'])->prefix('farmer')->name('farmer.')->group(function () {
    Route::get('/dashboard', [FarmerController::class, 'dashboard'])->name('dashboard');
    Route::get('/handoff', [VendorController::class, 'dashboard'])->name('handoff');
    Route::post('/handoff', [VendorController::class, 'storeHandoff'])->name('handoff.store');
    Route::post('/batches', [FarmerController::class, 'storeBatch'])->name('batches.store');
});

/*
|--------------------------------------------------------------------------
| Vendor Workspace (Collector, Wholesale, Retail)
|--------------------------------------------------------------------------
*/
Route::middleware(['role:collector,wholesale,retail'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::post('/collect', [VendorController::class, 'storeCollection'])->name('collect');
    Route::get('/dashboard', [VendorController::class, 'dashboard'])->name('dashboard');
    Route::post('/handoff', [VendorController::class, 'storeHandoff'])->name('handoff.store');
    Route::post('/transactions/{id}/void', [VendorController::class, 'voidTransaction'])->name('transactions.void');
});

/*
|--------------------------------------------------------------------------
| Authority Workspace (Market Monitor, Enforcement)
|--------------------------------------------------------------------------
*/
Route::middleware(['role:authority,admin'])->prefix('authority')->name('authority.')->group(function () {
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
    Route::get('/marketplaces', [MarketController::class, 'admin'])->name('marketplaces');
    Route::post('/marketplaces', [MarketController::class, 'store'])->middleware('password.confirm')->name('marketplaces.store');
    Route::post('/users/{id}/verify', [AdminController::class, 'verifyUser'])->middleware('password.confirm')->name('users.verify');
    Route::post('/users/{id}/toggle', [AdminController::class, 'toggleUserStatus'])->middleware('password.confirm')->name('users.toggle');
    Route::post('/users/{id}/trading', [AdminController::class, 'tradingApproval'])->middleware('password.confirm')->name('users.trading');
    Route::post('/review-assignments', [AdminController::class, 'assignReviewer'])->middleware('password.confirm')->name('review.assign');
    Route::post('/rules', [AdminController::class, 'storeRule'])->middleware('password.confirm')->name('rules.store');
    Route::post('/rules/{id}/toggle', [AdminController::class, 'toggleRule'])->middleware('password.confirm')->name('rules.toggle');
    Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
    Route::get('/batches/verification', [AdminController::class, 'batchVerificationIndex'])->name('batches.verification');
    Route::get('/batches/{batchId}', [AdminController::class, 'batchDetail'])->name('batches.detail');
    Route::post('/batches/{batchId}/verify', [AdminController::class, 'verifyBatch'])->name('batches.verify');
    Route::post('/batches/{batchId}/reject', [AdminController::class, 'rejectBatch'])->name('batches.reject');
});

Route::middleware('role:farmer,collector,wholesale,retail')->group(function () {
    Route::get('/handoffs', [HandoffController::class, 'index'])->name('handoffs.index');
    Route::post('/handoffs/{id}/decision', [HandoffController::class, 'decide'])->middleware('throttle:decisions')->name('handoffs.decide');
});
Route::get('/farmer-access', [FarmerAccessController::class, 'index'])->name('farmers.access');
Route::get('/retail/prices', [MarketController::class, 'prices'])->middleware('role:retail')->name('retail.prices');
Route::post('/retail/prices', [MarketController::class, 'recordPrice'])->middleware(['role:retail', 'throttle:purchases'])->name('retail.prices.store');
Route::post('/farmer-access/request', [FarmerAccessController::class, 'requestAccess'])->middleware('throttle:enrollment')->name('farmers.access.request');
Route::post('/farmer-access/redeem', [FarmerAccessController::class, 'redeem'])->middleware('throttle:confirmation')->name('farmers.access.redeem');
Route::post('/farmer-access/withdraw', [FarmerAccessController::class, 'withdraw'])->middleware('throttle:enrollment')->name('farmers.withdraw');
Route::post('/farmer-access/transactions/{id}/correction', [FarmerAccessController::class, 'correction'])->middleware('throttle:decisions')->name('farmers.access.correction');
Route::post('/admin/farmer-access/{id}/review', [FarmerAccessController::class, 'review'])->middleware(['role:admin', 'password.confirm'])->name('admin.farmer-access.review');
Route::get('/transactions/{id}/receipt', [HandoffController::class, 'receipt'])->middleware('role:admin,authority,farmer,collector,wholesale,retail,consumer')->name('transactions.receipt');

Route::middleware('role:farmer,collector,wholesale,retail')->group(function () {
    Route::get('/corrections', [CorrectionController::class, 'index'])->name('corrections.index');
    Route::post('/transactions/{id}/correction', [CorrectionController::class, 'store'])->middleware('throttle:decisions')->name('corrections.store');
    Route::post('/corrections/{id}/decision', [CorrectionController::class, 'decide'])->middleware('throttle:decisions')->name('corrections.decide');
});

Route::middleware('role:admin,authority,farmer,collector,wholesale,retail,consumer')->group(function () {
    Route::get('/confirm-password', fn () => view('auth.confirm-password'))->name('password.confirm');
    Route::post('/confirm-password', function (Request $request) {
        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Password is incorrect.']);
        }
        $request->session()->passwordConfirmed();

        return redirect()->intended(route('public.home'));
    })->middleware('throttle:confirmation')->name('password.confirm.store');
});
