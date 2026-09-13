<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Models\Announcement;

Route::get('/', function () {
    $announcements = Schema::hasTable('announcements')
        ? Announcement::visible()->latest()->limit(4)->get()
        : collect();
    return view('home', compact('announcements'));
})->name('home');
Route::get('/pricing', [EditorController::class, 'pricing'])->name('pricing');
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements');
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/refund-policy', 'legal.refunds')->name('refunds');
Route::view('/privacy', 'legal.privacy')->name('privacy');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login/phone', [AuthController::class, 'phoneContinue'])->middleware('throttle:10,10')->name('login.phone');
Route::get('/login/password', [AuthController::class, 'passwordForm'])->name('login.password');
Route::post('/login/password', [AuthController::class, 'passwordLogin'])->middleware('throttle:10,10')->name('login.password.store');
Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
Route::post('/register', [AuthController::class, 'registerStore'])->middleware('throttle:10,10')->name('register.store');
Route::post('/login/register/verify', [AuthController::class, 'verifyRegistrationOtp'])->middleware('throttle:10,10')->name('register.otp.verify');
Route::post('/login/request-otp', [AuthController::class, 'requestOtp'])->middleware('throttle:5,10')->name('login.otp');
Route::post('/login/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,10')->name('login.verify');
Route::get('/login/email', [AuthController::class, 'emailLoginForm'])->name('login.email');
Route::post('/login/email', [AuthController::class, 'requestEmailOtp'])->middleware('throttle:5,10')->name('login.email.request');
Route::post('/login/email/verify', [AuthController::class, 'verifyEmailOtp'])->middleware('throttle:10,10')->name('login.email.verify');
Route::get('/login/email/mobile', [AuthController::class, 'emailMobileForm'])->name('login.email.mobile');
Route::post('/login/email/mobile', [AuthController::class, 'requestEmailMobileOtp'])->middleware('throttle:5,10')->name('login.email.mobile.request');
Route::post('/login/email/mobile/verify', [AuthController::class, 'verifyEmailMobileOtp'])->middleware('throttle:10,10')->name('login.email.mobile.verify');
Route::get('/forgot-password', [AuthController::class, 'forgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetOtp'])->middleware('throttle:5,10')->name('password.send');
Route::post('/forgot-password/verify', [AuthController::class, 'verifyPasswordResetOtp'])->middleware('throttle:10,10')->name('password.verify');
Route::get('/forgot-password/reset', [AuthController::class, 'resetPasswordForm'])->name('password.reset');
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:10,10')->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/editor', [EditorController::class, 'create'])->name('editor');
Route::get('/editor/pending', [EditorController::class, 'pending'])->name('editor.pending');
Route::post('/editor/upload', [EditorController::class, 'upload'])->middleware('throttle:10,10')->name('editor.upload');

Route::middleware(['auth', 'single.editor'])->group(function () {
    Route::get('/dashboard', [EditorController::class, 'dashboard'])->name('dashboard');
    Route::post('/editor/analyze', [EditorController::class, 'analyze'])->middleware('throttle:20,10')->name('editor.analyze');
    Route::post('/editor/save', [EditorController::class, 'save'])->middleware('throttle:120,1')->name('editor.save');
    Route::post('/editor/feedback', [EditorController::class, 'feedback'])->middleware('throttle:60,10')->name('editor.feedback');
    Route::post('/editor/export/{format}', [ExportController::class, 'export'])->whereIn('format', ['docx', 'pdf'])->middleware('throttle:10,10')->name('editor.export');
    Route::post('/editor/heartbeat', [EditorController::class, 'heartbeat'])->middleware('throttle:60,1')->name('editor.heartbeat');
    Route::get('/support', [SupportController::class, 'index'])->name('support');
    Route::post('/support/tickets', [SupportController::class, 'create'])->middleware('throttle:10,10')->name('support.create');
    Route::post('/support/tickets/{ticket}/messages', [SupportController::class, 'message'])->middleware('throttle:30,10')->name('support.message');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::post('/wallet/top-up', [WalletController::class, 'topUp'])->middleware('throttle:10,10')->name('wallet.topup');
    Route::get('/checkout/{order}', [PaymentController::class, 'show'])->name('checkout');
    Route::post('/checkout/{order}/wallet', [PaymentController::class, 'payWithWallet'])->middleware('throttle:10,10')->name('checkout.wallet');
    Route::post('/documents/{document}/checkout', [PaymentController::class, 'createForDocument'])->middleware('throttle:10,10')->name('documents.checkout');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    Route::post('/pricing', [AdminController::class, 'updatePricing'])->name('pricing.update');
    Route::post('/users/{user}/capabilities', [AdminController::class, 'updateUserCapabilities'])->name('user.capabilities');
    Route::post('/users/{user}/block', [AdminController::class, 'toggleUser'])->name('user.toggle');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::post('/announcements/{announcement}/toggle', [AnnouncementController::class, 'toggle'])->name('announcements.toggle');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});
