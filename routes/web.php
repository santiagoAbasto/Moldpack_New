<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactInquiryController;
use App\Http\Controllers\ClientAreaController;
use App\Http\Controllers\Admin\ClientCommerceController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\SocialLinkController;
use App\Http\Controllers\Admin\DatabaseExportController;
use App\Http\Controllers\MoldpackAiController;
use Illuminate\Support\Facades\Route;

Route::middleware('client.public-exit')->group(function (): void {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/productos/{product}', [SiteController::class, 'product'])->name('products.show');
    Route::get('/novedades/{news}', [SiteController::class, 'news'])->name('news.show');
    Route::get('/donde-comprar', [SiteController::class, 'stores'])->name('stores');
    Route::get('/{slug}', [SiteController::class, 'page'])->whereIn('slug', ['nosotros', 'categorias', 'productos', 'catalogo', 'novedades', 'calidad', 'contacto'])->name('public.page');
});
Route::post('/contacto/consultas', [ContactInquiryController::class, 'store'])->middleware('throttle:contact')->name('contact.inquiries.store');
Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->middleware('throttle:newsletter')->name('newsletter.subscribe');
Route::get('/newsletter/baja/{token}', [NewsletterController::class, 'unsubscribe'])->middleware('throttle:newsletter')->name('newsletter.unsubscribe');
Route::get('/buscar/ia-moldpack', [MoldpackAiController::class, 'search'])->middleware('throttle:site-search')->name('moldpack-ai.search');
Route::post('/area-clientes/login', [ClientAreaController::class, 'login'])->middleware('throttle:client-login')->name('client.login');
Route::post('/area-clientes/registro', [ClientAreaController::class, 'register'])->middleware('throttle:client-register')->name('client.register');
Route::middleware('client')->group(function (): void {
    Route::get('/area-clientes', [ClientAreaController::class, 'dashboard'])->name('client.dashboard');
    Route::get('/area-clientes/{section}', [ClientAreaController::class, 'portal'])->whereIn('section', ['productos','carrito','pedidos','pagos','cuenta','facturas'])->name('client.portal');
    Route::post('/area-clientes/salir', [ClientAreaController::class, 'logout'])->name('client.logout');
    Route::post('/area-clientes/carrito', [ClientAreaController::class, 'addToCart'])->name('client.cart.add');
    Route::delete('/area-clientes/carrito/{product}', [ClientAreaController::class, 'removeFromCart'])->name('client.cart.remove');
    Route::patch('/area-clientes/carrito/{product}', [ClientAreaController::class, 'updateCart'])->name('client.cart.update');
    Route::post('/area-clientes/pedidos', [ClientAreaController::class, 'checkout'])->name('client.checkout');
    Route::post('/area-clientes/pedidos/{order}/recomprar', [ClientAreaController::class, 'reorder'])->name('client.orders.reorder');
    Route::post('/area-clientes/pagos', [ClientAreaController::class, 'reportPayment'])->name('client.payments.store');
    Route::get('/area-clientes/pagos/{payment}/comprobante', [ClientAreaController::class, 'downloadPaymentReceipt'])->name('client.payments.receipt');
    Route::get('/area-clientes/facturas/{invoice}/descargar', [ClientAreaController::class, 'downloadInvoice'])->name('client.invoices.download');
});
Route::redirect('/login', '/admin/login', 301);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::get('/dashboard', [AdminController::class, 'index'])->middleware(['auth', 'auth.session', 'admin'])->name('admin.dashboard');

Route::prefix('admin')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'show'])->name('admin.login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:admin-login')->name('admin.login.store');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('admin.logout');

    Route::middleware(['auth', 'auth.session', 'admin'])->group(function (): void {
        Route::get('/', [AdminController::class, 'index'])->name('admin.cms');
        Route::get('/database/export/mysql', [DatabaseExportController::class, 'mysql'])->name('admin.database.export.mysql');
        Route::get('/database/export/sqlite', [DatabaseExportController::class, 'sqlite'])->name('admin.database.export.sqlite');
        Route::put('/pages/{page}', [AdminController::class, 'savePage']);
        Route::post('/pages/{page}/sections', [AdminController::class, 'createSection']);
        Route::put('/sections/{section}', [AdminController::class, 'saveSection']);
        Route::delete('/sections/{section}', [AdminController::class, 'deleteSection']);
        Route::post('/sections/{section}/items', [AdminController::class, 'createItem']);
        Route::put('/items/{item}', [AdminController::class, 'saveItem']);
        Route::delete('/items/{item}', [AdminController::class, 'deleteItem']);
        Route::post('/media', [AdminController::class, 'upload']);
        Route::post('/documents', [AdminController::class, 'uploadDocument']);
        Route::delete('/media/{media}', [AdminController::class, 'deleteMedia']);
        Route::put('/settings/{key}', [AdminController::class, 'saveSetting'])->where('key', '[a-z_]+');
        Route::get('/stores/geocode', [AdminController::class, 'geocodeStore'])->middleware('throttle:20,1');
        Route::post('/newsletter/campaigns', [AdminNewsletterController::class, 'send']);
        Route::post('/social-links', [SocialLinkController::class, 'save']);
        Route::delete('/social-links/{id}', [SocialLinkController::class, 'delete']);
        Route::post('/pages/{page}/seo-image', [AdminController::class, 'uploadSeoImage']);
        Route::put('/consultas/{inquiry}/read', [AdminController::class, 'markInquiryRead']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{user}', [AdminController::class, 'saveUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
        Route::put('/clientes/{cliente}', [ClientCommerceController::class, 'updateClient']);
        Route::post('/clientes/{cliente}/password', [ClientCommerceController::class, 'updateClientPassword']);
        Route::post('/clientes/{cliente}/password/view', [ClientCommerceController::class, 'viewClientPassword']);
        Route::put('/pedidos/{order}', [ClientCommerceController::class, 'updateOrder']);
        Route::post('/pedidos/{order}/facturas', [ClientCommerceController::class, 'createInvoice']);
        Route::put('/facturas/{invoice}/anular', [ClientCommerceController::class, 'cancelInvoice']);
        Route::put('/pagos/{payment}', [ClientCommerceController::class, 'updatePayment']);
        Route::get('/zona-privada/exportar/{type}', [ClientCommerceController::class, 'export']);
        Route::get('/zona-privada/datos/{type}', [ClientCommerceController::class, 'data']);
    });
});
