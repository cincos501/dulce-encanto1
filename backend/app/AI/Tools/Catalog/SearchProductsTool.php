<?php

declare(strict_types=1);

namespace App\AI\Tools\Catalog;

use App\AI\Contracts\ToolInterface;
use App\Repositories\ProductRepositoryInterface;

class SearchProductsTool implements ToolInterface
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    public function getName(): string
    {
        return 'search_products';
    }

    public function getDescription(): string
    {
        return 'Buscar productos por nombre o descripción en el catálogo de la repostería Dulce Encanto.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Término de búsqueda opcional para el nombre o descripción del producto (ej: torta, chocolate, queque).'
                ]
            ],
            'required' => []
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $query = $arguments['query'] ?? null;
        $paginator = $this->productRepository->paginate(perPage: 15, search: $query, onlyActive: true);

        if ($paginator->isEmpty()) {
            return "No se encontraron productos en el catálogo que coincidan con la búsqueda '" . ($query ?? '') . "'.";
        }

        $result = "Productos encontrados en el catálogo:\n";
        foreach ($paginator->items() as $product) {
            $categoryName = $product->category ? $product->category->name : 'General';
            $result .= "- [ID: {$product->id}] {$product->name}: {$product->description} (Categoría: {$categoryName})\n";
        }

        return $result;
    }
}
