<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TendererProfileController;
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
        ->open()
        ->with(['county:id,name', 'industry:id,name', 'status:id,name', 'institution:id,institution_name,logo'])
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

// Static public pages
Route::get('/about-us', function () {
    return Inertia::render('AboutUs', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('about');

Route::get('/services', function () {
    return Inertia::render('Services', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('services');

Route::get('/services/procurement', fn() => Inertia::render('Services/ProcurementServices', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
]))->name('services.procurement');

Route::get('/services/bid-support', fn() => Inertia::render('Services/BidSupport', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
]))->name('services.bid-support');

Route::get('/services/marketplace', fn() => Inertia::render('Services/Marketplace', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
]))->name('services.marketplace');

Route::get('/services/funding', fn() => Inertia::render('Services/Funding', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
]))->name('services.funding');

Route::get('/contact-us', function () {
    return Inertia::render('ContactUs', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('contact');

Route::post('/contact-us', [\App\Http\Controllers\ContactController::class, 'send'])->name('contact.send');

// Public tenders search/results (10 per page)
Route::get('/tenders/search', [TenderController::class, 'publicSearch'])->name('tenders.search');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home/dashboard', [DashboardController::class, 'index'])->name('home.dashboard');
    Route::get('/my-applications', [\App\Http\Controllers\ApplicationController::class, 'myApplications'])->name('my.applications');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/tenderer', [TendererProfileController::class, 'update'])->name('profile.tenderer.update');
});

// Admin-only routes
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/tenders', [TenderController::class, 'index'])->name('tenders.index');
    Route::get('/tenders/create', [TenderController::class, 'create'])->name('tenders.create');
    Route::post('/tenders', [TenderController::class, 'store'])->name('tenders.store');
    Route::get('/tenders/{encryptedId}/edit', [TenderController::class, 'edit'])->name('tenders.edit');
    Route::put('/tenders/{encryptedId}', [TenderController::class, 'update'])->name('tenders.update');
    Route::get('/institutions', [InstitutionController::class, 'index'])->name('institutions.index');
    Route::post('/institutions', [InstitutionController::class, 'store'])->name('institutions.store');
    Route::post('/institutions/{institution}', [InstitutionController::class, 'update'])->name('institutions.update');
    Route::get('/admin/applications', [\App\Http\Controllers\Admin\ApplicationController::class, 'all'])->name('admin.applications.all');
    Route::get('/admin/tenders/{encryptedId}/applications', [\App\Http\Controllers\Admin\ApplicationController::class, 'index'])->name('admin.tenders.applications.index');
    Route::get('/admin/tenders/{encryptedId}/evaluate', [\App\Http\Controllers\Admin\ApplicationController::class, 'evaluate'])->name('admin.tenders.evaluate');
    Route::patch('/admin/tenders/{encryptedId}/process-status', [\App\Http\Controllers\Admin\ApplicationController::class, 'updateTenderProcessStatus'])->name('admin.tenders.process-status');
    Route::get('/admin/applications/{encryptedAppId}', [\App\Http\Controllers\Admin\ApplicationController::class, 'show'])->name('admin.applications.show');
    Route::patch('/admin/applications/{encryptedAppId}/status', [\App\Http\Controllers\Admin\ApplicationController::class, 'updateApplicationStatus'])->name('admin.applications.update-status');
    Route::patch('/admin/applications/{encryptedAppId}/rating', [\App\Http\Controllers\Admin\ApplicationController::class, 'updateApplicationRating'])->name('admin.applications.update-rating');
    Route::post('/admin/applications/{encryptedAppId}/notes', [\App\Http\Controllers\Admin\ApplicationController::class, 'addNote'])->name('admin.applications.add-note');
    Route::get('/admin/transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'index'])->name('admin.transactions.index');
    Route::get('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('admin.users.update');
    Route::patch('/admin/users/{user}/role', [\App\Http\Controllers\Admin\UserController::class, 'updateRole'])->name('admin.users.update-role');
    Route::patch('/admin/users/{user}/toggle-verified', [\App\Http\Controllers\Admin\UserController::class, 'toggleVerified'])->name('admin.users.toggle-verified');
    Route::patch('/admin/users/{user}/toggle-active', [\App\Http\Controllers\Admin\UserController::class, 'toggleActive'])->name('admin.users.toggle-active');
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

Route::middleware(['auth'])->group(function () {
    Route::post('/tenders/{slug}/draft', [\App\Http\Controllers\ApplicationDraftController::class, 'save'])->name('tenders.draft.save');
    Route::delete('/tenders/{slug}/draft', [\App\Http\Controllers\ApplicationDraftController::class, 'discard'])->name('tenders.draft.discard');
    Route::post('/applications/{id}/unsubmit', [\App\Http\Controllers\ApplicationController::class, 'unsubmit'])->name('applications.unsubmit');
});

require __DIR__ . '/auth.php';
