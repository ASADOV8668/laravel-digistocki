<?php

use App\Http\Controllers\Admin\AttributeController as AdminAttributeController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ListingController as AdminListingController;
use App\Http\Controllers\Admin\PhoneModelController as AdminPhoneModelController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\FavoriteController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\ListingController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\ReportController as UserReportController;
use App\Http\Controllers\User\StorefrontController;
use App\Http\Controllers\User\SupportController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/support', [SupportController::class, 'index'])->name('support.index');

Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
Route::get('/listings/autocomplete', [ListingController::class, 'autocomplete'])->middleware('throttle:60,1')->name('listings.autocomplete');
Route::get('/listings/search/suggestions', [ListingController::class, 'searchSuggestions'])->middleware('throttle:60,1')->name('listings.search.suggestions');
Route::get('/listings/models/{phoneModel}/attributes', [ListingController::class, 'modelAttributes'])->middleware('throttle:60,1')->name('listings.models.attributes');
Route::get('/locations/provinces/{province}/cities', [ListingController::class, 'cities'])->middleware('throttle:60,1')->name('locations.provinces.cities');
Route::get('/listings/create', [ListingController::class, 'create'])->middleware(['auth', 'active'])->name('listings.create');
Route::post('/listings', [ListingController::class, 'store'])->middleware(['auth', 'active'])->name('listings.store');
Route::get('/listings/{listing:slug}', [ListingController::class, 'show'])->name('listings.show');

Route::get('/dashboard', [UserDashboardController::class, 'index'])->middleware(['auth', 'active', 'verified'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/listings/{listing:slug}/favorite', [FavoriteController::class, 'toggle'])->name('listings.favorite.toggle');
    Route::post('/listings/{listing:slug}/report', [UserReportController::class, 'store'])->name('listings.report');
    Route::post('/listings/{listing:slug}/contact-otp', [ListingController::class, 'requestContactOtp'])->middleware('throttle:5,1')->name('listings.contact-otp');
    Route::post('/listings/{listing:slug}/contact-otp/verify', [ListingController::class, 'verifyContactOtp'])->middleware('throttle:5,1')->name('listings.contact-otp.verify');
    Route::get('/listings/{listing:slug}/edit', [ListingController::class, 'edit'])->name('listings.edit');
    Route::put('/listings/{listing:slug}', [ListingController::class, 'update'])->name('listings.update');
    Route::delete('/listings/{listing:slug}', [ListingController::class, 'destroy'])->name('listings.destroy');
    Route::patch('/listings/{listing:slug}/sold', [ListingController::class, 'markSold'])->name('listings.sold');
    Route::patch('/listings/{listing:slug}/renew', [ListingController::class, 'renew'])->name('listings.renew');
    Route::delete('/listings/{listing:slug}/images/{image}', [ListingController::class, 'destroyImage'])->name('listings.images.destroy');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/my-store', [StorefrontController::class, 'edit'])->name('storefront.edit');
    Route::put('/my-store', [StorefrontController::class, 'update'])->name('storefront.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/reports', [UserReportController::class, 'index'])->name('reports.index');
});

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', DashboardController::class)->name('admin.dashboard');
    Route::get('/listings', [AdminListingController::class, 'index'])->name('admin.listings.index');
    Route::get('/listings/create', [AdminListingController::class, 'create'])->name('admin.listings.create');
    Route::post('/listings', [AdminListingController::class, 'store'])->name('admin.listings.store');
    Route::get('/listings/{listing}/edit', [AdminListingController::class, 'edit'])->name('admin.listings.edit');
    Route::put('/listings/{listing}', [AdminListingController::class, 'update'])->name('admin.listings.update');
    Route::delete('/listings/{listing}/images/{image}', [AdminListingController::class, 'deleteImage'])->name('admin.listings.images.destroy');
    Route::get('/users/search', [AdminListingController::class, 'userSearch'])->middleware('throttle:60,1')->name('admin.users.search');
    Route::patch('/listings/{listing}/approve', [AdminListingController::class, 'approve'])->name('admin.listings.approve');
    Route::patch('/listings/{listing}/reject', [AdminListingController::class, 'reject'])->name('admin.listings.reject');
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::patch('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('admin.users.toggle-active');
    Route::patch('/users/{user}/toggle-role', [AdminUserController::class, 'toggleRole'])->name('admin.users.toggle-role');
    Route::patch('/users/{user}/toggle-listing-permission', [AdminUserController::class, 'toggleListingPermission'])->name('admin.users.toggle-listing-permission');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
    Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('admin.reports.status');
    Route::get('/brands', [AdminBrandController::class, 'index'])->name('admin.brands.index');
    Route::post('/brands', [AdminBrandController::class, 'store'])->name('admin.brands.store');
    Route::patch('/brands/{brand}', [AdminBrandController::class, 'update'])->name('admin.brands.update');
    Route::patch('/brands/{brand}/toggle', [AdminBrandController::class, 'toggle'])->name('admin.brands.toggle');
    Route::get('/phone-models', [AdminPhoneModelController::class, 'index'])->name('admin.phone-models.index');
    Route::post('/phone-models', [AdminPhoneModelController::class, 'store'])->name('admin.phone-models.store');
    Route::patch('/phone-models/{phoneModel}', [AdminPhoneModelController::class, 'update'])->name('admin.phone-models.update');
    Route::patch('/phone-models/{phoneModel}/toggle', [AdminPhoneModelController::class, 'toggle'])->name('admin.phone-models.toggle');
    Route::get('/attributes', [AdminAttributeController::class, 'index'])->name('admin.attributes.index');
    Route::post('/attributes', [AdminAttributeController::class, 'store'])->name('admin.attributes.store');
    Route::patch('/attributes/{attribute}', [AdminAttributeController::class, 'update'])->name('admin.attributes.update');
    Route::patch('/attributes/{attribute}/toggle', [AdminAttributeController::class, 'toggle'])->name('admin.attributes.toggle');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('admin.settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
});

require __DIR__.'/auth.php';
