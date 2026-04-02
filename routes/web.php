<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\MpesaController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Models\Tender;
use App\Models\Industry;
use App\Models\County;

Route::get('/', function () {
    $latest = Tender::query()
        ->with(['county:id,name', 'industry:id,name', 'status:id,name'])
        ->latest()
        ->take(6)
        ->get();

    $industries = Industry::query()->where('active', true)->select(['id', 'name'])->orderBy('name')->get();
    $counties = County::query()->where('active', true)->select(['id', 'name'])->orderBy('name')->get();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'tenders' => $latest,
        'industries' => $industries,
        'counties' => $counties,
    ]);
})->name('welcome');

// Public tenders search/results (10 per page)
Route::get('/tenders/search', [TenderController::class, 'publicSearch'])->name('tenders.search');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home/dashboard', [DashboardController::class, 'index'])->name('home.dashboard');
    Route::get('/tenders', [TenderController::class, 'index'])->name('tenders.index');
    Route::get('/tenders/create', [TenderController::class, 'create'])->name('tenders.create');
    Route::post('/tenders', [TenderController::class, 'store'])->name('tenders.store');
    Route::get('/tenders/{encryptedId}/edit', [TenderController::class, 'edit'])->name('tenders.edit');
    Route::put('/tenders/{encryptedId}', [TenderController::class, 'update'])->name('tenders.update');
    Route::get('/institutions', [InstitutionController::class, 'index'])->name('institutions.index');
    Route::post('/institutions', [InstitutionController::class, 'store'])->name('institutions.store');
    Route::post('/institutions/{institution}', [InstitutionController::class, 'update'])->name('institutions.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin applications routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/tenders/{encryptedId}/applications', [\App\Http\Controllers\Admin\ApplicationController::class, 'index'])->name('admin.tenders.applications.index');
    Route::get('/admin/applications/{encryptedAppId}', [\App\Http\Controllers\Admin\ApplicationController::class, 'show'])->name('admin.applications.show');
});

// M-Pesa
Route::post('/mpesa/stk-push', [MpesaController::class, 'stkPush'])->middleware('auth')->name('mpesa.stk_push');
Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback')->withoutMiddleware(['web']);
Route::get('/mpesa/poll/{checkoutRequestId}', [MpesaController::class, 'pollStatus'])->middleware('auth')->name('mpesa.poll');

// Google OAuth
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::get('/tenders/{slug}', [TenderController::class, 'publicShow'])->name('tenders.public.show');
Route::post('/tenders/{slug}/upload-file', [\App\Http\Controllers\ApplicationController::class, 'uploadTempFile'])->name('tenders.upload_file');
Route::post('/tenders/{slug}/apply', [\App\Http\Controllers\ApplicationController::class, 'store'])->name('tenders.apply');
Route::post('/tenders/{slug}/delete-temp-file', [\App\Http\Controllers\ApplicationController::class, 'deleteTempFile'])->name('tenders.delete_temp_file');

require __DIR__ . '/auth.php';
