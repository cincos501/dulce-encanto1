<?php

declare(strict_types=1);

namespace App\AI\Tools\Catalog;

use App\AI\Contracts\ToolInterface;
use App\Repositories\CategoryRepositoryInterface;

class SearchCategoriesTool implements ToolInterface
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function getName(): string
    {
        return 'search_categories';
    }

    public function getDescription(): string
    {
        return 'Obtener o buscar categorías de productos activas en la repostería Dulce Encanto.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Término de búsqueda opcional para el nombre o descripción de la categoría.'
                ]
            ],
            'required' => []
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $query = $arguments['query'] ?? null;

        if ($query && !$this->isGenericQuery($query)) {
            $paginator = $this->categoryRepository->paginate(perPage: 20, search: $query, onlyActive: true);
            $categories = collect($paginator->items());
        } else {
            $categories = $this->categoryRepository->all(onlyActive: true);
        }

        if ($categories->isEmpty()) {
            return "No se encontraron categorías activas que coincidan.";
        }

        $result = "Categorías de productos disponibles:\n";
        foreach ($categories as $category) {
            $result .= "- [ID: {$category->id}] {$category->name}: {$category->description}\n";
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
