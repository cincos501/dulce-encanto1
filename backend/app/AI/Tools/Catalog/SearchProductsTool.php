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

        if ($query && $this->isGenericQuery($query)) {
            $query = null; // Bypass text filter to retrieve all products
        }

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

    protected function isGenericQuery(?string $query): bool
    {
        if ($query === null || trim($query) === '') {
            return true;
        }
        $query = strtolower(trim($query));
        $normalize = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $query
        );
        $stopWords = ['muestrame', 'lista de', 'listado de', 'que tienen', 'que tiene', 'que hay', 'ver', 'mostrar', 'buscar', 'dame', 'que', 'quiero', 'tienen', 'tiene', 'hay', 'de', 'mis', 'los', 'las', 'un', 'una', 'unos', 'unas'];
        $clean = $normalize;
        foreach ($stopWords as $word) {
            $clean = str_replace($word, '', $clean);
        }
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        $generics = ['categoria', 'categorias', 'producto', 'productos', 'catalogo', 'todos', 'todas', 'promocion', 'promociones', 'descuento', 'descuentos', 'oferta', 'ofertas', 'extra', 'extras', 'adicional', 'adicionales'];
        return in_array($clean, $generics, true) || empty($clean);
    }
}
