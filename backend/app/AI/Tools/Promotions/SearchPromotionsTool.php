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

        if ($query) {
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
            $discount = $promo->discount_percentage ? " | Descuento: {$promo->discount_percentage}%" : '';
            $validity = '';
            if ($promo->start_date && $promo->end_date) {
                $validity = " | Válido desde: {$promo->start_date} hasta: {$promo->end_date}";
            }
            $result .= "- [ID Promo: {$promo->id}] {$promo->name}: {$promo->description}{$discount}{$validity}\n";
        }

        return $result;
    }
}
