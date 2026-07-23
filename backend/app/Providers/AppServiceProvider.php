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
use App\Repositories\SupplierRepository;
use App\Repositories\SupplierRepositoryInterface;
use App\Repositories\SupplyRepository;
use App\Repositories\SupplyRepositoryInterface;
use App\Repositories\RecipeRepository;
use App\Repositories\RecipeRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\ReportRepositoryInterface;
use App\Repositories\ReportRepository;
use App\Repositories\WhatsAppSessionRepositoryInterface;
use App\Repositories\RedisWhatsAppSessionRepository;
use App\AI\Contracts\ConversationMemoryInterface;
use App\AI\Memory\RedisConversationMemory;
use App\AI\Contracts\LLMProviderInterface;
use App\AI\Providers\GroqProvider;
use App\AI\Registry\ToolRegistry;
use App\AI\Tools\Catalog\SearchProductsTool;
use App\AI\Tools\Catalog\SearchCategoriesTool;
use App\AI\Tools\Catalog\SearchVariantsTool;
use App\AI\Tools\Catalog\SearchExtrasTool;
use App\AI\Tools\Promotions\SearchPromotionsTool;
use App\AI\Tools\Business\GetBusinessInfoTool;
use App\AI\Tools\Business\GetOpeningHoursTool;
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
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->bind(SupplyRepositoryInterface::class, SupplyRepository::class);
        $this->app->bind(RecipeRepositoryInterface::class, RecipeRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
        $this->app->bind(WhatsAppSessionRepositoryInterface::class, RedisWhatsAppSessionRepository::class);
        $this->app->bind(ConversationMemoryInterface::class, RedisConversationMemory::class);
        $this->app->bind(LLMProviderInterface::class, GroqProvider::class);

        // Auto-discover and register all AI Tools under app/AI/Tools
        $toolsPath = realpath(app_path('AI/Tools'));
        if ($toolsPath && is_dir($toolsPath)) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($toolsPath));
            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $filePath = $file->getRealPath();
                    $normalizedToolsPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $toolsPath);
                    $normalizedFilePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
                    
                    $relativePath = str_replace([$normalizedToolsPath, '.php'], ['', ''], $normalizedFilePath);
                    $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);
                    
                    $className = 'App\\AI\\Tools\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                    
                    if (class_exists($className) && is_subclass_of($className, \App\AI\Contracts\ToolInterface::class)) {
                        $this->app->singleton($className);
                        $this->app->tag($className, 'ai_tools');
                    }
                }
            }
        }

        $this->app->singleton(ToolRegistry::class, function ($app) {
            $tools = [];
            foreach ($app->tagged('ai_tools') as $tool) {
                $tools[] = $tool;
            }
            return new ToolRegistry($tools);
        });

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
