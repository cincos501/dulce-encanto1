<?php

declare(strict_types=1);

namespace App\AI\Tools\Promotions;

use App\AI\Contracts\ToolInterface;
use App\Repositories\PromotionRepositoryInterface;

class SearchPromotionsTool implements ToolInterface
{
    public function __construct(
        protected PromotionRepositoryInterface $promotionRepository
    ) {}

    public function getName(): string
    {
        return 'search_promotions';
    }

    public function getDescription(): string
    {
        return 'Buscar promociones y ofertas activas en la repostería Dulce Encanto.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Término de búsqueda opcional para el nombre o descripción de la promoción.'
                ]
            ],
            'required' => []
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        $query = $arguments['query'] ?? null;

        if ($query && !$this->isGenericQuery($query)) {
            $paginator = $this->promotionRepository->paginate(perPage: 15, search: $query, onlyActive: true);
            $promotions = collect($paginator->items());
        } else {
            $promotions = $this->promotionRepository->all(onlyActive: true);
        }

        if ($promotions->isEmpty()) {
            return "No hay promociones activas actualmente que coincidan.";
        }

        $result = "Promociones y ofertas activas:\n";
        foreach ($promotions as $promo) {
            $discountVal = (float) $promo->discount;
            $discount = " | Descuento: " . ($promo->discount_type === 'percentage' ? "{$discountVal}%" : "Bs. {$discountVal}");
            $validity = '';
            if ($promo->start_date && $promo->end_date) {
                $validity = " | Válido desde: {$promo->start_date->format('Y-m-d')} hasta: {$promo->end_date->format('Y-m-d')}";
            }
            $result .= "- [ID Promo: {$promo->id}] {$promo->name}: {$promo->description}{$discount}{$validity}\n";
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
