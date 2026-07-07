<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StorageServiceInterface;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function __construct(
        protected StorageServiceInterface $storageService
    ) {}

    /**
     * Display the public product catalog list.
     */
    public function index(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->whereHas('category', static function ($q): void {
                $q->where('is_active', true);
            })
            ->whereHas('variants', static function ($q): void {
                $q->where('is_active', true);
            })
            ->with([
                'category',
                'variants' => static function ($q): void {
                    $q->where('is_active', true)->with(['images']);
                },
            ])
            ->get();

        $catalog = $products->map(function ($product) {
            $minPrice = $product->variants->min('base_price');

            // Find first primary image from active variants
            $primaryImage = null;
            foreach ($product->variants as $variant) {
                $primary = $variant->images->firstWhere('is_primary', true);
                if ($primary) {
                    $primaryImage = $this->storageService->url($primary->image_url);
                    break;
                }
            }

            // Fallback to first variant image if no primary is explicitly set
            if ($primaryImage === null) {
                foreach ($product->variants as $variant) {
                    if ($variant->images->isNotEmpty()) {
                        $primaryImage = $this->storageService->url($variant->images->first()->image_url);
                        break;
                    }
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category->name,
                'min_price' => $minPrice !== null ? (float) $minPrice : 0.00,
                'image' => $primaryImage,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Catálogo público recuperado con éxito.',
            'data' => $catalog,
        ]);
    }

    /**
     * Display a single product's detail in the catalog.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::where('is_active', true)
            ->whereHas('category', static function ($q): void {
                $q->where('is_active', true);
            })
            ->with([
                'category',
                'variants' => static function ($q): void {
                    $q->where('is_active', true)->with([
                        'images',
                        'extras' => static function ($extQ): void {
                            $extQ->where('is_active', true);
                        },
                    ]);
                },
                'promotions' => static function ($q): void {
                    $q->where('is_active', true)
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now());
                },
            ])
            ->findOrFail($id);

        // Gather gallery images from active variants
        $gallery = [];
        foreach ($product->variants as $variant) {
            foreach ($variant->images as $img) {
                $gallery[] = [
                    'id' => $img->id,
                    'image_url' => $this->storageService->url($img->image_url),
                    'is_primary' => (bool) $img->is_primary,
                ];
            }
        }

        // Sort gallery to put primary image first
        usort($gallery, static fn ($a, $b) => ($b['is_primary'] ? 1 : 0) - ($a['is_primary'] ? 1 : 0));

        // Gather active extras (unique by extra ID)
        $extras = collect();
        foreach ($product->variants as $variant) {
            foreach ($variant->extras as $extra) {
                $extras->put($extra->id, [
                    'id' => $extra->id,
                    'name' => $extra->name,
                    'description' => $extra->description,
                    'price' => (float) $extra->price,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle del producto recuperado con éxito.',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category->name,
                'gallery' => $gallery,
                'variants' => $product->variants->map(static fn ($variant) => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'price' => (float) $variant->base_price,
                ]),
                'extras' => $extras->values()->all(),
                'promotions' => $product->promotions->map(static fn ($promo) => [
                    'id' => $promo->id,
                    'name' => $promo->name,
                    'description' => $promo->description,
                    'discount_type' => $promo->discount_type,
                    'discount' => (float) $promo->discount,
                ]),
            ],
        ]);
    }
}
