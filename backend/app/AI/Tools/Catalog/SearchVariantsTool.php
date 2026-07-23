<?php

declare(strict_types=1);

namespace App\AI\Tools\Catalog;

use App\AI\Contracts\ToolInterface;
use App\Repositories\ProductVariantRepositoryInterface;

class SearchVariantsTool implements ToolInterface
{
    public function __construct(
        protected ProductVariantRepositoryInterface $variantRepository
    ) {}

    public function getName(): string
    {
        return 'search_variants';
    }

    public function getDescription(): string
    {
        return 'Buscar las presentaciones (variantes) de los productos del catálogo, incluyendo precio, stock y porciones (sirve para cuántas personas).';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'product_id' => [
                    'type' => 'integer',
                    'description' => 'ID de producto opcional para filtrar las presentaciones de un producto específico.'
                ],
                'query' => [
                    'type' => 'string',
                    'description' => 'Término de búsqueda opcional para el nombre de la presentación.'
                ]
            ],
            'required' => []
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $productId = isset($arguments['product_id']) ? (int) $arguments['product_id'] : null;
        $query = $arguments['query'] ?? null;

        $paginator = $this->variantRepository->paginate(
            perPage: 15,
            search: $query,
            productId: $productId,
            onlyActive: true
        );

        if ($paginator->isEmpty()) {
            return "No se encontraron presentaciones o variantes de productos activas.";
        }

        $result = "Presentaciones de productos encontradas:\n";
        foreach ($paginator->items() as $variant) {
            $productName = $variant->product ? $variant->product->name : 'Producto';
            $portions = $variant->serves_people ? " (sirve para {$variant->serves_people} personas)" : '';
            $stock = $variant->stock !== null ? " | Stock: {$variant->stock}" : '';
            $result .= "- [ID Variante: {$variant->id}] {$productName} - Presentación: {$variant->name} | Precio: Bs. {$variant->price}{$portions}{$stock} | SKU: {$variant->sku}\n";
        }

        return $result;
    }
}
