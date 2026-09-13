<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface AudioTranscriberInterface
{
    /**
     * Transcribe un archivo de audio al idioma configurado.
     *
     * @param  string  $audio  Contenido binario del audio.
     * @param  string  $mime  Tipo MIME (p. ej. "audio/ogg").
     * @return string|null Transcripción, o null si no se pudo transcribir.
     */
    public function transcribe(string $audio, string $mime): ?string;
}
