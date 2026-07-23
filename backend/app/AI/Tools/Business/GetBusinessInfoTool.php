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
        return "Información General de la Repostería Dulce Encanto:\n" .
               "- **Descripción**: Repostería artesanal premium, especializada en tortas de chocolate, tres leches, queques y pasteles personalizados para toda ocasión. Horneamos felicidad para cada ocasión.\n" .
               "- **Dirección**: Calle Los Claveles #456, Cochabamba, Bolivia\n" .
               "- **Teléfono de contacto**: +591 4 4567890 / +591 70012345\n" .
               "- **Divisa oficial**: Bolivianos (Bs.)";
    }
}
