<?php

declare(strict_types=1);

namespace App\AI\Tools\Catalog;

use App\AI\Contracts\ToolInterface;
use App\Repositories\ExtraRepositoryInterface;

class SearchExtrasTool implements ToolInterface
{
    public function __construct(
        protected ExtraRepositoryInterface $extraRepository
    ) {}

    public function getName(): string
    {
        return 'search_extras';
    }

    public function getDescription(): string
    {
        return 'Obtener o buscar adicionales (extras) que se pueden agregar a las variantes de productos en la repostería.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Término de búsqueda opcional para el nombre o descripción del extra.'
                ]
            ],
            'required' => []
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $query = $arguments['query'] ?? null;

        if ($query && !$this->isGenericQuery($query)) {
            $paginator = $this->extraRepository->paginate(perPage: 20, search: $query, onlyActive: true);
            $extras = collect($paginator->items());
        } else {
            $extras = $this->extraRepository->all(onlyActive: true);
        }

        if ($extras->isEmpty()) {
            return "No se encontraron adicionales o extras activos.";
        }

        $result = "Adicionales (extras) disponibles en el catálogo:\n";
        foreach ($extras as $extra) {
            $result .= "- [ID Extra: {$extra->id}] {$extra->name} (Nota: El precio de los adicionales varía según la presentación/variante de producto elegida. Utiliza 'get_variant_extras' con el ID de la variante para consultar el precio exacto).\n";
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
