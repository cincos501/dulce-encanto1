<?php

declare(strict_types=1);

namespace App\AI\Media;

/**
 * Resultado de resolver los adjuntos de un mensaje entrante:
 *  - promptSegments: fragmentos de texto en español que se inyectan al turno del cliente.
 *  - privateNotes:   notas internas para el agente humano en Chatwoot.
 */
class ResolvedMedia
{
    /**
     * @param  list<string>  $promptSegments
     * @param  list<string>  $privateNotes
     */
    public function __construct(
        public readonly array $promptSegments = [],
        public readonly array $privateNotes = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->promptSegments === [];
    }

    public function promptText(): string
    {
        return implode("\n\n", $this->promptSegments);
    }
}
