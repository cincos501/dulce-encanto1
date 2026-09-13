<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface ImageAnalyzerInterface
{
    /**
     * Clasifica una imagen enviada por el cliente.
     *
     * @param  string  $image  Contenido binario de la imagen.
     * @param  string  $mime  Tipo MIME (p. ej. "image/jpeg").
     * @return array{category: 'comprobante_pago'|'diseno_referencia'|'foto_producto'|'otro', summary: string}|null
     */
    public function analyze(string $image, string $mime): ?array;
}
