<?php

declare(strict_types=1);

namespace App\AI\Media;

use App\AI\Contracts\AudioTranscriberInterface;
use App\AI\Contracts\ImageAnalyzerInterface;
use App\Services\ChatwootMediaService;
use Illuminate\Support\Facades\Log;

/**
 * Convierte los adjuntos de un mensaje de WhatsApp (audio, imágenes, stickers,
 * ubicación) en texto en español que el ConversationOrchestrator puede procesar.
 */
class IncomingMediaResolver
{
    public function __construct(
        protected ChatwootMediaService $media,
        protected ?AudioTranscriberInterface $transcriber = null,
        protected ?ImageAnalyzerInterface $vision = null,
    ) {}

    /**
     * @param list<array{
     *   kind: string, url: ?string, file_type: string, extension: ?string,
     *   latitude: ?float, longitude: ?float
     * }> $refs Salida de ChatwootAttachmentExtractor::fromPayload()
     */
    public function resolve(array $refs): ResolvedMedia
    {
        $segments = [];
        $notes = [];

        foreach ($refs as $ref) {
            $kind = $ref['kind'] ?? 'file';

            match ($kind) {
                'audio' => $this->handleAudio($ref, $segments, $notes),
                'sticker' => $segments[] = '[El cliente envió un STICKER, sin texto. Salúdalo con calidez en una sola '
                                          .'frase breve y ofrécele ayuda con nuestros postres, tortas y pedidos.]',
                'image' => $this->handleImage($ref, $segments, $notes),
                'location' => $this->handleLocation($ref, $segments, $notes),
                'video' => $segments[] = '[El cliente envió un video que no puedes ver. Pídele amablemente que '
                                          .'describa por texto lo que necesita.]',
                default => $segments[] = '[El cliente envió un archivo adjunto que no puedes procesar. Pídele '
                                          .'amablemente que te dé los detalles por texto.]',
            };
        }

        return new ResolvedMedia(array_values($segments), array_values($notes));
    }

    /**
     * @param  array{url: ?string}  $ref
     * @param  list<string>  $segments
     * @param  list<string>  $notes
     */
    private function handleAudio(array $ref, array &$segments, array &$notes): void
    {
        $fallback = '[El cliente envió una nota de voz que no se pudo transcribir. Pídele amablemente que '
                  .'escriba su consulta.]';

        if ($this->transcriber === null || empty($ref['url'])) {
            $segments[] = $fallback;

            return;
        }

        $file = $this->media->download($ref['url'], (int) config('ai.media.transcription.max_bytes', 16 * 1024 * 1024));
        $text = $file !== null ? $this->transcriber->transcribe($file['contents'], $file['mime']) : null;

        if ($text === null || trim($text) === '') {
            $segments[] = $fallback;
            $notes[] = '🎙️ [IA Encantito]: Llegó una nota de voz pero no se pudo transcribir.';

            return;
        }

        Log::info('IncomingMediaResolver: nota de voz transcrita', ['chars' => strlen($text)]);

        $segments[] = "El cliente envió una nota de voz. Transcripción textual de lo que dijo: \"{$text}\". "
                    .'Responde a su mensaje con naturalidad, como si lo hubiera escrito.';
        $notes[] = "🎙️ [IA Encantito - Transcripción de audio]: \"{$text}\"";
    }

    /**
     * @param  array{url: ?string}  $ref
     * @param  list<string>  $segments
     * @param  list<string>  $notes
     */
    private function handleImage(array $ref, array &$segments, array &$notes): void
    {
        $analysis = null;

        if (config('ai.media.vision.enabled') && $this->vision !== null && ! empty($ref['url'])) {
            $file = $this->media->download($ref['url'], (int) config('ai.media.vision.max_bytes', 8 * 1024 * 1024));
            if ($file !== null) {
                $analysis = $this->vision->analyze($file['contents'], $file['mime']);
            }
        }

        $category = $analysis['category'] ?? 'otro';
        $summary = trim((string) ($analysis['summary'] ?? ''));
        $summaryTxt = $summary !== '' ? " ({$summary})" : '';

        [$segment, $note] = match ($category) {
            'comprobante_pago' => [
                "El cliente envió una IMAGEN que parece un COMPROBANTE DE PAGO{$summaryTxt}. "
                .'Confirma que recibiste el comprobante, agradece y avísale que el equipo verificará el pago y le '
                .'confirmará en breve. NO des el pago por confirmado tú mismo.',
                "💳 [IA Encantito]: El cliente envió un posible comprobante de pago. Revisar y validar manualmente.{$summaryTxt}",
            ],
            'diseno_referencia' => [
                "El cliente envió una IMAGEN de DISEÑO DE REFERENCIA para una torta{$summaryTxt}. "
                .'Confirma que recibiste la foto de referencia y avísale que un repostero la revisará para el pedido. '
                .'Puedes continuar tomando el resto de la orden.',
                "🎂 [IA Encantito]: Diseño de referencia recibido. Derivar a repostería.{$summaryTxt}",
            ],
            'foto_producto' => [
                "El cliente envió una foto de un producto y probablemente pregunta por él{$summaryTxt}. "
                .'Pídele que te indique el nombre del producto o qué desea saber y usa las herramientas del catálogo '
                .'para ayudarlo.',
                "🖼️ [IA Encantito]: Foto de producto recibida.{$summaryTxt}",
            ],
            default => [
                'El cliente envió una imagen. Si parece un comprobante de pago o un diseño de pastel de referencia, '
                .'confírmale la recepción y avísale que el equipo o el repostero la revisará. Si no está claro, '
                .'pídele amablemente que aclare por texto qué necesita.',
                '🖼️ [IA Encantito]: Imagen recibida del cliente (sin clasificar).',
            ],
        };

        $segments[] = $segment;
        $notes[] = $note;
    }

    /**
     * @param  array{latitude: ?float, longitude: ?float}  $ref
     * @param  list<string>  $segments
     * @param  list<string>  $notes
     */
    private function handleLocation(array $ref, array &$segments, array &$notes): void
    {
        $lat = $ref['latitude'] ?? null;
        $long = $ref['longitude'] ?? null;

        if ($lat === null || $long === null) {
            $segments[] = '[El cliente intentó compartir su ubicación pero no se recibieron coordenadas. Pídele la '
                        .'dirección escrita con referencias.]';

            return;
        }

        $maps = "https://maps.google.com/?q={$lat},{$long}";

        $segments[] = 'El cliente COMPARTIÓ SU UBICACIÓN actual (por ejemplo para el delivery). '
                    ."Coordenadas: {$lat},{$long} — Google Maps: {$maps}. "
                    .'Agradece y usa esta ubicación como dirección de entrega; si necesitas una referencia adicional '
                    .'(color de casa, piso, timbre) pídesela en una sola frase.';
        $notes[] = "📍 [IA Encantito]: Ubicación compartida por el cliente → {$maps}";
    }
}
