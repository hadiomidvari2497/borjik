<?php

use App\Http\Controllers\MarketingController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AnalyticsCollectionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyListingController;
use App\Http\Controllers\PropertyListingPackageController;
use App\Http\Controllers\PropertyListingReportController;
use App\Http\Controllers\PropertyListingInquiryController;

Route::post('/analytics/heartbeat', [AnalyticsCollectionController::class, 'heartbeat'])->middleware('throttle:120,1')->name('analytics.heartbeat');
Route::post('/analytics/event', [AnalyticsCollectionController::class, 'event'])->middleware('throttle:120,1')->name('analytics.event');

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/subscription-plans', [MarketingController::class, 'subscriptionPlans'])->name('marketing.subscription-plans');
Route::get('/enamad/image', [MarketingController::class, 'enamadImage'])->name('marketing.enamad.image');
Route::get('/payment/gateway/callback', [SubscriptionController::class, 'callback'])->name('payment.gateway.callback');
Route::get('/properties/packages/callback', [PropertyListingPackageController::class, 'callback'])->name('properties.packages.callback');
Route::get('/software-building-charges', [MarketingController::class, 'buildingCharges'])->name('marketing.building-charges');
Route::get('/charge-management-guide', [MarketingController::class, 'chargeManagementGuide'])->name('marketing.charge-guide');
Route::get('/software-building-management', [MarketingController::class, 'buildingManagement'])->name('marketing.building-management');
Route::get('/articles', [MarketingController::class, 'articles'])->name('marketing.articles.index');
Route::get('/articles/{slug}', [MarketingController::class, 'article'])->name('marketing.articles.show');
Route::get('/software-building-requests', [MarketingController::class, 'seoPage'])->defaults('slug', 'software-building-requests')->name('marketing.seo.requests');
Route::get('/software-building-finance', [MarketingController::class, 'seoPage'])->defaults('slug', 'software-building-finance')->name('marketing.seo.finance');
Route::get('/building-management-software-comparison', [MarketingController::class, 'seoPage'])->defaults('slug', 'building-management-software-comparison')->name('marketing.seo.comparison');
Route::get('/trust-and-security', [MarketingController::class, 'seoPage'])->defaults('slug', 'trust-and-security')->name('marketing.seo.trust');



Route::get('/properties', [PropertyListingController::class, 'index'])->name('properties.index');
Route::get('/properties/{listing}', [PropertyListingController::class, 'show'])->whereNumber('listing')->name('properties.show');

Route::middleware('auth')->group(function () {
    Route::get('/my/properties', [PropertyListingController::class, 'mine'])->name('properties.mine');
    Route::get('/my/property-inquiries', [PropertyListingInquiryController::class, 'index'])->name('properties.inquiries.index');
    Route::patch('/my/property-inquiries/{inquiry}', [PropertyListingInquiryController::class, 'update'])->whereNumber('inquiry')->name('properties.inquiries.update');
    Route::post('/properties/{listing}/inquiries', [PropertyListingInquiryController::class, 'store'])->whereNumber('listing')->middleware('throttle:5,10')->name('properties.inquiries.store');
    Route::post('/properties/packages/buy', [PropertyListingPackageController::class, 'buy'])->middleware('throttle:10,1')->name('properties.packages.buy');
    Route::post('/properties/{listing}/reports', [PropertyListingReportController::class, 'store'])->whereNumber('listing')->middleware('throttle:5,10')->name('properties.reports.store');
    Route::get('/properties/create', [PropertyListingController::class, 'create'])->name('properties.create');
    Route::post('/properties', [PropertyListingController::class, 'store'])->middleware('throttle:10,1')->name('properties.store');
    Route::get('/properties/{listing}/edit', [PropertyListingController::class, 'edit'])->whereNumber('listing')->name('properties.edit');
    Route::put('/properties/{listing}', [PropertyListingController::class, 'update'])->whereNumber('listing')->name('properties.update');
    Route::delete('/properties/{listing}', [PropertyListingController::class, 'destroy'])->whereNumber('listing')->name('properties.destroy');
    Route::post('/properties/{listing}/close', [PropertyListingController::class, 'close'])->whereNumber('listing')->name('properties.close');
});

require __DIR__.'/auth.php';
require __DIR__.'/app.php';
require __DIR__.'/admin.php';
require __DIR__.'/calendar.php';
