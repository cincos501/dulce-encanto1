<?php

declare(strict_types=1);

namespace App\AI\Tools\Business;

use App\AI\Contracts\ToolInterface;

class GetBakeryLocationTool implements ToolInterface
{
    public function getName(): string
    {
        return 'get_bakery_location';
    }

    public function getDescription(): string
    {
        return 'Devuelve la dirección exacta y el enlace de Google Maps de la pastelería Dulce Encanto. '
             .'Úsala cuando el cliente pregunte dónde están ubicados, cómo llegar, cuál es la dirección '
             .'o pida "su ubicación"/"el mapa".';
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
        $loc = (array) config('business.location');
        $address = (string) ($loc['address'] ?? '');
        $mapsUrl = (string) ($loc['maps_url'] ?? '');
        $reference = trim((string) ($loc['reference'] ?? ''));

        $lines = [
            'UBICACIÓN DE LA PASTELERÍA (muéstrala tal cual al cliente, con el enlace en una línea aparte para que WhatsApp genere la tarjeta de mapa):',
            "📍 Dirección: {$address}",
        ];

        if ($reference !== '') {
            $lines[] = "🔎 Referencia: {$reference}";
        }

        $lines[] = "🗺️ Google Maps: {$mapsUrl}";

        return implode("\n", $lines);
    }
}
