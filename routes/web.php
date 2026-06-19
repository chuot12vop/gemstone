<?php

use App\Http\Controllers\Admin\AboutAdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BrandAdminController;
use App\Http\Controllers\Admin\CertificateAdminController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\ContactAdminController;
use App\Http\Controllers\Admin\CustomCssAdminController;
use App\Http\Controllers\Admin\CurrencyAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\PaymentAdminController;
use App\Http\Controllers\Admin\PageAdminController;
use App\Http\Controllers\Admin\PostAdminController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\ReviewAdminController;
use App\Http\Controllers\Admin\InterfaceAdminController;
use App\Http\Controllers\Admin\SettingAdminController;
use App\Http\Controllers\Shop\AccountController;
use App\Http\Controllers\Shop\Auth\GoogleAuthController;
use App\Http\Controllers\Shop\Auth\LoginController;
use App\Http\Controllers\Shop\Auth\RegisterController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\ContactController;
use App\Http\Controllers\Shop\CurrencyController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\PostController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\ReviewController;
use App\Http\Controllers\Shop\PromoSignupController;
use App\Http\Controllers\Shop\WelcomeOfferController;
use App\Http\Controllers\PayPalWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Shop (storefront) — customers use table `users` (email/password or Google)
*/
Route::get('/', [HomeController::class, 'index'])->name('shop.home');
Route::post('/welcome-offer', [WelcomeOfferController::class, 'store'])->name('shop.welcome.offer');
Route::post('/promo-signup', [PromoSignupController::class, 'store'])->name('shop.promo.signup');
Route::post('/webhooks/paypal', PayPalWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.paypal');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('shop.login');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('shop.register');
    Route::get('/forgot-password', [\App\Http\Controllers\Shop\Auth\ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Shop\Auth\ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Shop\Auth\ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Shop\Auth\ResetPasswordController::class, 'store'])->name('password.update');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('shop.logout');

Route::middleware('auth')->prefix('account')->name('shop.account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::post('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [AccountController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order_number}', [AccountController::class, 'orderShow'])->name('orders.show');
});
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::get('/catalog', [CatalogController::class, 'index'])->name('shop.catalog');
Route::get('/catalog/{category}', [CatalogController::class, 'category'])->name('shop.catalog.category');
Route::get('/product', [CatalogController::class, 'products'])->name('shop.products.index');
Route::get('/product/{product}', [ProductController::class, 'show'])->name('shop.product');
Route::get('/news', [PostController::class, 'index'])->name('shop.news.index');
Route::get('/news/{post}', [PostController::class, 'show'])->name('shop.post.show');
Route::get('/cart', [CartController::class, 'index'])->name('shop.cart');
Route::get('/cart/bag-fragment', [CartController::class, 'bagFragment'])->name('shop.cart.bag-fragment');
Route::post('/cart/add', [CartController::class, 'add'])->name('shop.cart.add');
Route::post('/cart/add-bundle', [CartController::class, 'addBundle'])->name('shop.cart.add-bundle');
Route::post('/cart/update', [CartController::class, 'update'])->name('shop.cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('shop.cart.remove');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('shop.checkout');
Route::post('/checkout/method', [CheckoutController::class, 'chooseMethod'])->name('shop.checkout.method');
Route::get('/checkout/details', [CheckoutController::class, 'details'])->name('shop.checkout.details');
Route::post('/checkout/voucher', [CheckoutController::class, 'applyVoucher'])->name('shop.checkout.voucher.apply');
Route::delete('/checkout/voucher', [CheckoutController::class, 'removeVoucher'])->name('shop.checkout.voucher.remove');
Route::post('/checkout/place', [CheckoutController::class, 'place'])->middleware('throttle:20,1')->name('shop.checkout.place');
Route::post('/checkout/express/paypal', [CheckoutController::class, 'expressPaypal'])->middleware('throttle:20,1')->name('shop.checkout.express.paypal');
Route::post('/checkout/cancel/{order_number}', [CheckoutController::class, 'cancelPending'])->name('shop.checkout.cancel');
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
        Route::get('/products/search', [ProductAdminController::class, 'search'])->name('products.search');
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
        Route::delete('/categories/{category}', [CategoryAdminController::class, 'destroy'])->name('categories.destroy');

        Route::get('/posts', [PostAdminController::class, 'index'])->name('posts.index');
        Route::get('/posts/create', [PostAdminController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostAdminController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}/edit', [PostAdminController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}', [PostAdminController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [PostAdminController::class, 'destroy'])->name('posts.destroy');

        Route::get('/pages', [PageAdminController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [PageAdminController::class, 'create'])->name('pages.create');
        Route::post('/pages', [PageAdminController::class, 'store'])->name('pages.store');
        Route::get('/pages/{page}/edit', [PageAdminController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [PageAdminController::class, 'update'])->name('pages.update');
        Route::delete('/pages/{page}', [PageAdminController::class, 'destroy'])->name('pages.destroy');

        Route::get('/brands', [BrandAdminController::class, 'index'])->name('brands.index');
        Route::get('/brands/create', [BrandAdminController::class, 'create'])->name('brands.create');
        Route::post('/brands', [BrandAdminController::class, 'store'])->name('brands.store');
        Route::get('/brands/{brand}/edit', [BrandAdminController::class, 'edit'])->name('brands.edit');
        Route::put('/brands/{brand}', [BrandAdminController::class, 'update'])->name('brands.update');
        Route::delete('/brands/{brand}', [BrandAdminController::class, 'destroy'])->name('brands.destroy');

        Route::get('/certificates', [CertificateAdminController::class, 'index'])->name('certificates.index');
        Route::get('/certificates/create', [CertificateAdminController::class, 'create'])->name('certificates.create');
        Route::post('/certificates', [CertificateAdminController::class, 'store'])->name('certificates.store');
        Route::get('/certificates/{certificate}/edit', [CertificateAdminController::class, 'edit'])->name('certificates.edit');
        Route::put('/certificates/{certificate}', [CertificateAdminController::class, 'update'])->name('certificates.update');
        Route::delete('/certificates/{certificate}', [CertificateAdminController::class, 'destroy'])->name('certificates.destroy');

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

        Route::get('/interface', [InterfaceAdminController::class, 'index'])->name('interface.index');
        Route::post('/interface/save', [InterfaceAdminController::class, 'save'])->name('interface.save');

        Route::get('/custom-css', [CustomCssAdminController::class, 'index'])->name('custom-css.index');
        Route::put('/custom-css/{viewport}', [CustomCssAdminController::class, 'update'])
            ->whereIn('viewport', array_keys(\App\Support\CustomThemeStylesheet::VIEWPORTS))
            ->name('custom-css.update');

        Route::get('/about', [AboutAdminController::class, 'index'])->name('about.index');
        Route::post('/about/save', [AboutAdminController::class, 'save'])->name('about.save');
    });
});
