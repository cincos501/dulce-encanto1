<?php

declare(strict_types=1);

namespace App\AI\Tools\Catalog;

use App\AI\Contracts\ToolInterface;
use App\Repositories\ProductVariantRepositoryInterface;

class GetVariantExtrasTool implements ToolInterface
{
    public function __construct(
        protected ProductVariantRepositoryInterface $variantRepository
    ) {}

    public function getName(): string
    {
        return 'get_variant_extras';
    }

    public function getDescription(): string
    {
        return 'Obtener la lista de adicionales (extras) disponibles y compatibles para una presentación/variante de producto específica (variant_id) con sus respectivos precios.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'variant_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la presentación/variante del producto para la cual obtener los adicionales disponibles.'
                ]
            ],
            'required' => ['variant_id']
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $variantId = (int) $arguments['variant_id'];

        $variant = $this->variantRepository->findById($variantId);
        if (!$variant) {
            return json_encode([
                'success' => false,
                'error' => "La presentación de producto con ID {$variantId} no fue encontrada en el catálogo."
            ], JSON_UNESCAPED_UNICODE);
        }

        $extras = [];
        // Loop over variant extras loaded from relationship
        foreach ($variant->extras as $extra) {
            if ($extra->is_active) {
                $extras[] = [
                    'id' => (int) $extra->id,
                    'name' => $extra->name,
                    'price' => (float) $extra->pivot->price
                ];
            }
        }

        return json_encode([
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'extras' => $extras
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
