<?php

declare(strict_types=1);

namespace App\Providers;

use App\AI\Contracts\AudioTranscriberInterface;
use App\AI\Contracts\ConversationMemoryInterface;
use App\AI\Contracts\ImageAnalyzerInterface;
use App\AI\Contracts\LLMProviderInterface;
use App\AI\Contracts\ToolInterface;
use App\AI\Media\GeminiAudioTranscriber;
use App\AI\Media\GeminiVisionAnalyzer;
use App\AI\Media\IncomingMediaResolver;
use App\AI\Media\OpenAIWhisperTranscriber;
use App\AI\Memory\RedisConversationMemory;
use App\AI\Providers\GeminiProvider;
use App\AI\Providers\GroqProvider;
use App\AI\Providers\OpenAIProvider;
use App\AI\Registry\ToolRegistry;
use App\Baneco\Contracts\EncryptionServiceInterface;
use App\Baneco\Services\Aes256EncryptionService;
use App\Events\ReadyStockOutOfStock;
use App\Events\SupplyStockLow;
use App\Listeners\HandleReadyStockOutOfStock;
use App\Listeners\HandleSupplyStockLow;
use App\Models\Order;
use App\Observers\OrderObserver;
use App\Repositories\CategoryRepository;
use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\ExtraRepository;
use App\Repositories\ExtraRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\ProductImageRepository;
use App\Repositories\ProductImageRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\ProductVariantRepository;
use App\Repositories\ProductVariantRepositoryInterface;
use App\Repositories\PromotionRepository;
use App\Repositories\PromotionRepositoryInterface;
use App\Repositories\RecipeRepository;
use App\Repositories\RecipeRepositoryInterface;
use App\Repositories\RedisWhatsAppSessionRepository;
use App\Repositories\ReportRepository;
use App\Repositories\ReportRepositoryInterface;
use App\Repositories\SupplierRepository;
use App\Repositories\SupplierRepositoryInterface;
use App\Repositories\SupplyRepository;
use App\Repositories\SupplyRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\WhatsAppSessionRepositoryInterface;
use App\Services\ChatwootMediaService;
use App\Services\StorageServiceInterface;
use App\Services\SupabaseStorageService;
use Illuminate\Support\Facades\Event;
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
        $this->app->bind(LLMProviderInterface::class, function ($app) {
            $provider = config('ai.provider');
            if ($provider === 'openai') {
                return $app->make(OpenAIProvider::class);
            }
            if ($provider === 'gemini') {
                return $app->make(GeminiProvider::class);
            }

            return $app->make(GroqProvider::class);
        });

        // Transcripción de audios entrantes (driver configurable; null = desactivado).
        $this->app->bind(AudioTranscriberInterface::class, function () {
            return match (config('ai.media.transcription.driver')) {
                'openai_whisper' => new OpenAIWhisperTranscriber,
                'gemini' => new GeminiAudioTranscriber,
                default => null,
            };
        });

        // Análisis visual de imágenes entrantes (opcional; null = desactivado).
        $this->app->bind(ImageAnalyzerInterface::class, function () {
            if (! config('ai.media.vision.enabled')) {
                return null;
            }

            return match (config('ai.media.vision.driver')) {
                'gemini' => new GeminiVisionAnalyzer,
                default => null,
            };
        });

        $this->app->singleton(IncomingMediaResolver::class, function ($app) {
            return new IncomingMediaResolver(
                $app->make(ChatwootMediaService::class),
                $app->make(AudioTranscriberInterface::class),
                $app->make(ImageAnalyzerInterface::class),
            );
        });

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

                    $className = 'App\\AI\\Tools\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

                    if (class_exists($className) && is_subclass_of($className, ToolInterface::class)) {
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

        // Bind Baneco Encryption Service
        $this->app->singleton(EncryptionServiceInterface::class, Aes256EncryptionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        Event::listen(
            ReadyStockOutOfStock::class,
            [HandleReadyStockOutOfStock::class, 'handle']
        );

        Event::listen(
            SupplyStockLow::class,
            [HandleSupplyStockLow::class, 'handle']
        );
    }
}
