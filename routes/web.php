<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\ContactAdminController;
use App\Http\Controllers\Admin\CurrencyAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\PaymentAdminController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\ReviewAdminController;
use App\Http\Controllers\Admin\SettingAdminController;
use App\Http\Controllers\Shop\Auth\GoogleAuthController;
use App\Http\Controllers\Shop\Auth\LoginController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\ContactController;
use App\Http\Controllers\Shop\CurrencyController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\ReviewController;
use Illuminate\Support\Facades\Route;

/*
| Shop (storefront) — customers use table `users`, sign in with Google
*/
Route::get('/', [HomeController::class, 'index'])->name('shop.home');
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('shop.logout');
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::get('/catalog', [CatalogController::class, 'index'])->name('shop.catalog');
Route::get('/catalog/{category}', [CatalogController::class, 'category'])->name('shop.catalog.category');
Route::get('/product', [CatalogController::class, 'products'])->name('shop.products.index');
Route::get('/product/{product}', [ProductController::class, 'show'])->name('shop.product');
Route::get('/cart', [CartController::class, 'index'])->name('shop.cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('shop.cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('shop.cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('shop.cart.remove');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('shop.checkout');
Route::post('/checkout/method', [CheckoutController::class, 'chooseMethod'])->name('shop.checkout.method');
Route::get('/checkout/details', [CheckoutController::class, 'details'])->name('shop.checkout.details');
Route::post('/checkout/place', [CheckoutController::class, 'place'])->name('shop.checkout.place');
Route::get('/checkout/processing/{order_number}', [CheckoutController::class, 'processing'])->name('shop.checkout.processing');
Route::post('/checkout/confirm/{order_number}', [CheckoutController::class, 'confirm'])->name('shop.checkout.confirm');
Route::get('/order/{order_number}', [CheckoutController::class, 'confirmation'])->name('shop.order.show');
Route::get('/order/{order_number}/review/{orderItem}', [ReviewController::class, 'create'])->name('shop.review.create');
Route::post('/order/{order_number}/review/{orderItem}', [ReviewController::class, 'store'])->name('shop.review.store');
Route::post('/currency', [CurrencyController::class, 'set'])->name('shop.currency');
Route::get('/about', [PageController::class, 'about'])->name('shop.about');
Route::get('/about/gemstones', function () {
    return view('shop.about.gemstones');
})->name('shop.about.gemstones');
Route::get('/about/spirituality', function () {
    return view('shop.about.spirituality');
})->name('shop.about.spirituality');
Route::get('/about/wealth', function () {
    return view('shop.about.wealth');
})->name('shop.about.wealth');
Route::get('/contact', [PageController::class, 'contact'])->name('shop.contact');
Route::post('/contact', [ContactController::class, 'store'])->name('shop.contact.store');
Route::get('/security-policy', [PageController::class, 'securityPolicy'])->name('shop.policy.security');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('shop.policy.privacy');
Route::get('/return-policy', [PageController::class, 'returnPolicy'])->name('shop.policy.return');
Route::get('/terms-of-service', [PageController::class, 'termsOfService'])->name('shop.policy.terms');
Route::get('/retail-policy', [PageController::class, 'retailPolicy'])->name('shop.policy.retail');

/*
| Admin — table `admins`, session guard `admin`
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:admin')->name('logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/products', [ProductAdminController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductAdminController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductAdminController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductAdminController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductAdminController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductAdminController::class, 'destroy'])->name('products.destroy');

        Route::get('/categories', [CategoryAdminController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryAdminController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryAdminController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryAdminController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryAdminController::class, 'update'])->name('categories.update');

        Route::get('/currency', [CurrencyAdminController::class, 'index'])->name('currency.index');
        Route::post('/currency/save', [CurrencyAdminController::class, 'save'])->name('currency.save');

        Route::get('/orders', [OrderAdminController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderAdminController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/status', [OrderAdminController::class, 'status'])->name('orders.status');

        Route::get('/payments', [PaymentAdminController::class, 'index'])->name('payments.index');
        Route::post('/payments/settings', [PaymentAdminController::class, 'saveSettings'])->name('payments.settings');

        Route::get('/contacts', [ContactAdminController::class, 'index'])->name('contacts.index');
        Route::get('/contacts/{contact}', [ContactAdminController::class, 'show'])->name('contacts.show');
        Route::post('/contacts/{contact}/status', [ContactAdminController::class, 'status'])->name('contacts.status');
        Route::delete('/contacts/{contact}', [ContactAdminController::class, 'destroy'])->name('contacts.destroy');

        Route::get('/reviews', [ReviewAdminController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/create', [ReviewAdminController::class, 'create'])->name('reviews.create');
        Route::post('/reviews', [ReviewAdminController::class, 'store'])->name('reviews.store');
        Route::get('/reviews/{review}/edit', [ReviewAdminController::class, 'edit'])->name('reviews.edit');
        Route::put('/reviews/{review}', [ReviewAdminController::class, 'update'])->name('reviews.update');
        Route::delete('/reviews/{review}', [ReviewAdminController::class, 'destroy'])->name('reviews.destroy');

        Route::get('/settings', [SettingAdminController::class, 'index'])->name('settings.index');
        Route::post('/settings/save', [SettingAdminController::class, 'save'])->name('settings.save');
    });
});
