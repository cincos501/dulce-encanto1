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

        if ($query) {
            $paginator = $this->extraRepository->paginate(perPage: 20, search: $query, onlyActive: true);
            $extras = collect($paginator->items());
        } else {
            $extras = $this->extraRepository->all(onlyActive: true);
        }

        if ($extras->isEmpty()) {
            return "No se encontraron adicionales o extras activos.";
        }

        $result = "Adicionales (extras) disponibles:\n";
        foreach ($extras as $extra) {
            $result .= "- [ID Extra: {$extra->id}] {$extra->name} | Precio: Bs. {$extra->price}\n";
        }

        return $result;
    }
}
