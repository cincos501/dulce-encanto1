<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\CategoryRepository;
use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\ExtraRepository;
use App\Repositories\ExtraRepositoryInterface;
use App\Repositories\ProductImageRepository;
use App\Repositories\ProductImageRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\ProductVariantRepository;
use App\Repositories\ProductVariantRepositoryInterface;
use App\Repositories\PromotionRepository;
use App\Repositories\PromotionRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use App\Services\StorageServiceInterface;
use App\Services\SupabaseStorageService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(ExtraRepositoryInterface::class, ExtraRepository::class);
        $this->app->bind(PromotionRepositoryInterface::class, PromotionRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductVariantRepositoryInterface::class, ProductVariantRepository::class);
        $this->app->bind(ProductImageRepositoryInterface::class, ProductImageRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Bind StorageServiceInterface as a singleton to reuse Supabase connection details
        $this->app->singleton(StorageServiceInterface::class, SupabaseStorageService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
