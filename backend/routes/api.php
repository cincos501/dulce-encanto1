<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Catalog endpoints (no auth required)
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/promotions', [CatalogController::class, 'promotions'])->name('catalog.promotions');
    Route::get('/catalog/{id}', [CatalogController::class, 'show'])->name('catalog.show');
    Route::post('/checkout', [OrderController::class, 'store'])->name('checkout.store');

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
    });

    Route::middleware(['auth:sanctum', 'user.active'])->group(function () {
        // Auth endpoints
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        // Categories CRUD endpoints
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->middleware('permission:categories.view')->name('categories.index');
            Route::post('/', [CategoryController::class, 'store'])->middleware('permission:categories.create')->name('categories.store');
            Route::get('/{category}', [CategoryController::class, 'show'])->middleware('permission:categories.view')->name('categories.show');
            Route::put('/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update')->name('categories.update');
            Route::patch('/{category}/toggle-active', [CategoryController::class, 'toggleActive'])->middleware('permission:categories.update')->name('categories.toggle-active');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('categories.destroy');
        });

        // Extras CRUD endpoints
        Route::prefix('extras')->group(function () {
            Route::get('/', [ExtraController::class, 'index'])->middleware('permission:extras.view')->name('extras.index');
            Route::post('/', [ExtraController::class, 'store'])->middleware('permission:extras.create')->name('extras.store');
            Route::get('/{extra}', [ExtraController::class, 'show'])->middleware('permission:extras.view')->name('extras.show');
            Route::put('/{extra}', [ExtraController::class, 'update'])->middleware('permission:extras.update')->name('extras.update');
            Route::patch('/{extra}/toggle-active', [ExtraController::class, 'toggleActive'])->middleware('permission:extras.update')->name('extras.toggle-active');
            Route::delete('/{extra}', [ExtraController::class, 'destroy'])->middleware('permission:extras.delete')->name('extras.destroy');
        });

        // Promotions CRUD endpoints
        Route::prefix('promotions')->group(function () {
            Route::get('/', [PromotionController::class, 'index'])->middleware('permission:promotions.view')->name('promotions.index');
            Route::post('/', [PromotionController::class, 'store'])->middleware('permission:promotions.create')->name('promotions.store');
            Route::get('/{promotion}', [PromotionController::class, 'show'])->middleware('permission:promotions.view')->name('promotions.show');
            Route::put('/{promotion}', [PromotionController::class, 'update'])->middleware('permission:promotions.update')->name('promotions.update');
            Route::patch('/{promotion}/toggle-active', [PromotionController::class, 'toggleActive'])->middleware('permission:promotions.update')->name('promotions.toggle-active');
            Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->middleware('permission:promotions.delete')->name('promotions.destroy');
        });

        // Products CRUD endpoints
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
            Route::post('/', [ProductController::class, 'store'])->middleware('permission:products.create')->name('products.store');
            Route::get('/{product}', [ProductController::class, 'show'])->middleware('permission:products.view')->name('products.show');
            Route::put('/{product}', [ProductController::class, 'update'])->middleware('permission:products.update')->name('products.update');
            Route::patch('/{product}/toggle-active', [ProductController::class, 'toggleActive'])->middleware('permission:products.update')->name('products.toggle-active');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete')->name('products.destroy');
        });

        // Product Variants CRUD endpoints
        Route::prefix('product-variants')->group(function () {
            Route::get('/', [ProductVariantController::class, 'index'])->middleware('permission:product_variants.view')->name('product-variants.index');
            Route::post('/', [ProductVariantController::class, 'store'])->middleware('permission:product_variants.create')->name('product-variants.store');
            Route::get('/{product_variant}', [ProductVariantController::class, 'show'])->middleware('permission:product_variants.view')->name('product-variants.show');
            Route::put('/{product_variant}', [ProductVariantController::class, 'update'])->middleware('permission:product_variants.update')->name('product-variants.update');
            Route::patch('/{product_variant}/toggle-active', [ProductVariantController::class, 'toggleActive'])->middleware('permission:product_variants.update')->name('product-variants.toggle-active');
            Route::delete('/{product_variant}', [ProductVariantController::class, 'destroy'])->middleware('permission:product_variants.delete')->name('product-variants.destroy');
        });

        // Product Images CRUD endpoints protected by product_variants permissions
        Route::prefix('product-images')->group(function () {
            Route::get('/', [ProductImageController::class, 'index'])->middleware('permission:product_variants.view')->name('product-images.index');
            Route::post('/', [ProductImageController::class, 'store'])->middleware('permission:product_variants.update')->name('product-images.store');
            Route::patch('/{product_image}/set-primary', [ProductImageController::class, 'setPrimary'])->middleware('permission:product_variants.update')->name('product-images.set-primary');
            Route::delete('/{product_image}', [ProductImageController::class, 'destroy'])->middleware('permission:product_variants.delete')->name('product-images.destroy');
        });

        // Users CRUD endpoints
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->middleware('permission:users.manage')->name('users.index');
            Route::post('/', [UserController::class, 'store'])->middleware('permission:users.manage')->name('users.store');
            Route::get('/{user}', [UserController::class, 'show'])->middleware('permission:users.manage')->name('users.show');
            Route::put('/{user}', [UserController::class, 'update'])->middleware('permission:users.manage')->name('users.update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('permission:users.manage')->name('users.destroy');
            Route::put('/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:users.manage')->name('users.reset-password');
            Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])->middleware('permission:users.manage')->name('users.toggle-active');
        });

        // Suppliers CRUD endpoints
        Route::prefix('suppliers')->group(function () {
            Route::get('/', [SupplierController::class, 'index'])->middleware('permission:suppliers.view')->name('suppliers.index');
            Route::post('/', [SupplierController::class, 'store'])->middleware('permission:suppliers.create')->name('suppliers.store');
            Route::get('/{supplier}', [SupplierController::class, 'show'])->middleware('permission:suppliers.view')->name('suppliers.show');
            Route::put('/{supplier}', [SupplierController::class, 'update'])->middleware('permission:suppliers.update')->name('suppliers.update');
            Route::patch('/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->middleware('permission:suppliers.update')->name('suppliers.toggle-active');
        });

        // Supplies CRUD endpoints
        Route::prefix('supplies')->group(function () {
            Route::get('/', [SupplyController::class, 'index'])->middleware('permission:supplies.view')->name('supplies.index');
            Route::post('/', [SupplyController::class, 'store'])->middleware('permission:supplies.create')->name('supplies.store');
            Route::get('/{supply}', [SupplyController::class, 'show'])->middleware('permission:supplies.view')->name('supplies.show');
            Route::put('/{supply}', [SupplyController::class, 'update'])->middleware('permission:supplies.update')->name('supplies.update');
            Route::patch('/{supply}/toggle-active', [SupplyController::class, 'toggleActive'])->middleware('permission:supplies.update')->name('supplies.toggle-active');
            Route::post('/purchase', [SupplyController::class, 'purchase'])->middleware('permission:supplies.update')->name('supplies.purchase');
        });

        // Recipes CRUD endpoints
        Route::prefix('recipes')->group(function () {
            Route::get('/', [RecipeController::class, 'index'])->middleware('permission:recipes.view')->name('recipes.index');
            Route::get('/{product_variant}', [RecipeController::class, 'show'])->middleware('permission:recipes.view')->name('recipes.show');
            Route::put('/{product_variant}', [RecipeController::class, 'update'])->middleware('permission:recipes.update')->name('recipes.update');
        });

        // Orders CRUD / Production endpoints
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
            Route::get('/customers/list', [OrderController::class, 'customers'])->middleware('permission:orders.view')->name('orders.customers');
            Route::get('/{order}', [OrderController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
            Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->middleware('permission:orders.update')->name('orders.update-status');
        });

        // Reports endpoints
        Route::prefix('reports')->middleware('permission:reports.view')->group(function () {
            Route::get('/summary', [ReportController::class, 'summary'])->name('reports.summary');
            Route::get('/sales', [ReportController::class, 'sales'])->name('reports.sales');
            Route::get('/products', [ReportController::class, 'products'])->name('reports.products');
            Route::get('/supplies', [ReportController::class, 'supplies'])->name('reports.supplies');
            Route::get('/production', [ReportController::class, 'production'])->name('reports.production');
            
            Route::get('/sales/export-excel', [ReportController::class, 'exportSalesExcel'])->name('reports.sales.excel');
            Route::get('/sales/export-pdf', [ReportController::class, 'exportSalesPdf'])->name('reports.sales.pdf');
            Route::get('/products/export-excel', [ReportController::class, 'exportProductsExcel'])->name('reports.products.excel');
            Route::get('/products/export-pdf', [ReportController::class, 'exportProductsPdf'])->name('reports.products.pdf');
            Route::get('/supplies/export-excel', [ReportController::class, 'exportSuppliesExcel'])->name('reports.supplies.excel');
            Route::get('/supplies/export-pdf', [ReportController::class, 'exportSuppliesPdf'])->name('reports.supplies.pdf');
            Route::get('/production/export-excel', [ReportController::class, 'exportProductionExcel'])->name('reports.production.excel');
            Route::get('/production/export-pdf', [ReportController::class, 'exportProductionPdf'])->name('reports.production.pdf');
        });
    });
});

// Chatwoot Webhook endpoint (public with token validation in Request)
Route::post('/webhooks/chatwoot', [\App\Http\Controllers\Api\Webhooks\ChatwootWebhookController::class, 'handle'])
    ->name('webhooks.chatwoot');

// Baneco Webhook endpoint (public)
Route::post('/webhooks/baneco/payment', [\App\Baneco\Http\Controllers\BanecoWebhookController::class, 'notifyPaymentQR'])
    ->name('webhooks.baneco.payment');
