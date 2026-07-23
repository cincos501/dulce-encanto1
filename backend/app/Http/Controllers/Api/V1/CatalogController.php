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
                    $q->where('is_active', true)->with([
                        'images',
                        'promotions' => static function ($promoQ): void {
                            $promoQ->where('is_active', true)
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now());
                        },
                    ]);
                },
            ])
            ->get();

        $catalog = $products->map(function ($product) {
            $minPrice = $product->variants->min('price');

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

            $hasPromotion = false;
            $lowestPromoPrice = null;
            $correspondingOriginalPrice = null;
            $discountText = null;

            // Filter variants that have active promotions
            $promoVariants = $product->variants->filter(fn($v) => $v->promotions->isNotEmpty());

            if ($promoVariants->isNotEmpty()) {
                $hasPromotion = true;
                foreach ($promoVariants as $variant) {
                    $activePromo = $variant->promotions->first();
                    if ($activePromo->discount_type === 'percentage') {
                        $promoPrice = $variant->price - ($variant->price * $activePromo->discount / 100);
                    } else {
                        $promoPrice = max(0, $variant->price - $activePromo->discount);
                    }
                    $promoPrice = (float) $promoPrice;

                    if ($lowestPromoPrice === null || $promoPrice < $lowestPromoPrice) {
                        $lowestPromoPrice = $promoPrice;
                        $correspondingOriginalPrice = (float) $variant->price;
                        $discountText = $activePromo->discount_type === 'percentage'
                            ? '-' . round((float) $activePromo->discount) . '%'
                            : '-Bs. ' . number_format((float) $activePromo->discount, 2);
                    }
                }
            } else {
                $correspondingOriginalPrice = $minPrice !== null ? (float) $minPrice : 0.00;
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category->name,
                'min_price' => $correspondingOriginalPrice,
                'promo_price' => $lowestPromoPrice,
                'has_promotion' => $hasPromotion,
                'promo_discount_text' => $discountText,
                'has_multiple_variants' => $product->variants->count() > 1,
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
                        'promotions' => static function ($promoQ): void {
                            $promoQ->where('is_active', true)
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now());
                        },
                    ]);
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
                    'price' => (float) $extra->pivot->price,
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
                'variants' => $product->variants->map(static function ($variant) {
                    $activePromo = $variant->promotions->first();
                    $promoPrice = null;
                    if ($activePromo) {
                        if ($activePromo->discount_type === 'percentage') {
                            $promoPrice = $variant->price - ($variant->price * $activePromo->discount / 100);
                        } else {
                            $promoPrice = max(0, $variant->price - $activePromo->discount);
                        }
                        $promoPrice = (float) $promoPrice;
                    }
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                        'price' => (float) $variant->price,
                        'promo_price' => $promoPrice,
                        'serves_people' => $variant->serves_people !== null ? (int) $variant->serves_people : null,
                        'extras' => $variant->extras->map(static function ($extra) {
                            return [
                                'id' => $extra->id,
                                'name' => $extra->name,
                                'description' => $extra->description,
                                'price' => (float) $extra->pivot->price,
                            ];
                        })->all(),
                    ];
                }),
                'extras' => $extras->values()->all(),
                'promotions' => collect($product->variants)->flatMap(static function ($v) {
                    return $v->promotions;
                })->unique('id')->values()->map(function ($promo) {
                    return [
                        'id' => $promo->id,
                        'name' => $promo->name,
                        'description' => $promo->description,
                        'discount_type' => $promo->discount_type,
                        'discount' => (float) $promo->discount,
                        'image_url' => $promo->image_url ? $this->storageService->url($promo->image_url) : null,
                    ];
                })->all(),
            ],
        ]);
    }

    /**
     * Display active promotions for the public catalog.
     */
    public function promotions(): JsonResponse
    {
        $promotions = \App\Models\Promotion::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with(['variants' => function ($q) {
                $q->where('is_active', true)->with(['product' => function ($prodQ) {
                    $prodQ->where('is_active', true)->whereHas('category', function ($catQ) {
                        $catQ->where('is_active', true);
                    });
                }, 'images']);
            }])
            ->get();

        $data = $promotions->map(function ($promo) {
            $products = [];
            foreach ($promo->variants as $variant) {
                if ($variant->product && $variant->product->is_active) {
                    $productId = $variant->product->id;
                    if (!isset($products[$productId])) {
                        $primaryImage = null;
                        $primary = $variant->images->firstWhere('is_primary', true);
                        if ($primary) {
                            $primaryImage = $this->storageService->url($primary->image_url);
                        } else if ($variant->images->isNotEmpty()) {
                            $primaryImage = $this->storageService->url($variant->images->first()->image_url);
                        }
                        
                        $products[$productId] = [
                            'id' => $variant->product->id,
                            'name' => $variant->product->name,
                            'description' => $variant->product->description,
                            'category' => $variant->product->category?->name,
                            'image' => $primaryImage,
                            'variants_with_discount' => []
                        ];
                    }
                    
                    $originalPrice = (float) $variant->price;
                    $promoPrice = $promo->discount_type === 'percentage'
                        ? $originalPrice - ($originalPrice * $promo->discount / 100)
                        : max(0, $originalPrice - $promo->discount);

                    $products[$productId]['variants_with_discount'][] = [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'price' => $originalPrice,
                        'promo_price' => (float) $promoPrice,
                        'discount_text' => $promo->discount_type === 'percentage'
                            ? '-' . round((float) $promo->discount) . '%'
                            : '-Bs. ' . number_format((float) $promo->discount, 2)
                    ];
                }
            }

            return [
                'id' => $promo->id,
                'name' => $promo->name,
                'description' => $promo->description,
                'discount_type' => $promo->discount_type,
                'discount' => (float) $promo->discount,
                'start_date' => $promo->start_date->toIso8601String(),
                'end_date' => $promo->end_date->toIso8601String(),
                'image_url' => $promo->image_url ? $this->storageService->url($promo->image_url) : null,
                'products' => array_values($products)
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Promociones públicas recuperadas con éxito.',
            'data' => $data
        ]);
    }
}
