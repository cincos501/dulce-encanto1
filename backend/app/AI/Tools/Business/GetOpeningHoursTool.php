<?php

declare(strict_types=1);

namespace App\AI\Tools\Business;

use App\AI\Contracts\ToolInterface;

class GetOpeningHoursTool implements ToolInterface
{
    public function getName(): string
    {
        return 'get_opening_hours';
    }

    public function getDescription(): string
    {
        return 'Obtener el horario de atención al público de la repostería Dulce Encanto.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }

    public function execute(array $arguments, array $context = []): string
    {
        return "Horario de Atención de Dulce Encanto:\n" .
               "- Lunes a Viernes: 08:30 a 19:30\n" .
               "- Sábado: 09:00 a 18:00\n" .
               "- Domingo: 09:00 a 13:00";
    }
}
