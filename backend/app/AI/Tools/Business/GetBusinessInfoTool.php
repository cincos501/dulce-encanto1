<?php

declare(strict_types=1);

namespace App\AI\Tools\Business;

use App\AI\Contracts\ToolInterface;

class GetBusinessInfoTool implements ToolInterface
{
    public function getName(): string
    {
        return 'get_business_info';
    }

    public function getDescription(): string
    {
        return 'Obtener información general de la repostería Dulce Encanto (ubicación, teléfono de contacto y descripción).';
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
        $name = (string) config('business.name', 'Dulce Encanto');
        $description = (string) config('business.description', '');
        $address = (string) config('business.location.address', '');
        $mapsUrl = (string) config('business.location.maps_url', '');
        $phone = (string) config('business.phone', '');

        return "Información General de la Repostería {$name}:\n".
               "- **Descripción**: {$description}\n".
               "- **Dirección**: {$address}\n".
               "- **Google Maps**: {$mapsUrl}\n".
               "- **Teléfono de contacto**: {$phone}\n".
               '- **Divisa oficial**: Bolivianos (Bs.)';
    }
}
