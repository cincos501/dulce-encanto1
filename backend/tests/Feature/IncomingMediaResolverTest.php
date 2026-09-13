<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Contracts\AudioTranscriberInterface;
use App\AI\Contracts\ImageAnalyzerInterface;
use App\AI\Media\IncomingMediaResolver;
use App\Services\ChatwootMediaService;
use Mockery;
use Tests\TestCase;

class IncomingMediaResolverTest extends TestCase
{
    private function mediaService(?array $downloadReturn = ['contents' => 'BIN', 'mime' => 'audio/ogg']): ChatwootMediaService
    {
        $mock = Mockery::mock(ChatwootMediaService::class);
        $mock->shouldReceive('download')->andReturn($downloadReturn);

        return $mock;
    }

    public function test_audio_is_transcribed_into_prompt_segment_and_private_note(): void
    {
        $transcriber = Mockery::mock(AudioTranscriberInterface::class);
        $transcriber->shouldReceive('transcribe')->once()->andReturn('Quiero una torta de chocolate');

        $resolver = new IncomingMediaResolver($this->mediaService(), $transcriber, null);

        $result = $resolver->resolve([
            ['kind' => 'audio', 'url' => 'https://cw.test/a.ogg', 'latitude' => null, 'longitude' => null],
        ]);

        $this->assertStringContainsString('Quiero una torta de chocolate', $result->promptText());
        $this->assertNotEmpty($result->privateNotes);
    }

    public function test_audio_without_transcriber_degrades_gracefully(): void
    {
        $resolver = new IncomingMediaResolver($this->mediaService(), null, null);

        $result = $resolver->resolve([
            ['kind' => 'audio', 'url' => 'https://cw.test/a.ogg', 'latitude' => null, 'longitude' => null],
        ]);

        $this->assertStringContainsString('no se pudo transcribir', $result->promptText());
    }

    public function test_sticker_produces_brief_greeting_instruction(): void
    {
        $resolver = new IncomingMediaResolver($this->mediaService(), null, null);

        $result = $resolver->resolve([
            ['kind' => 'sticker', 'url' => null, 'latitude' => null, 'longitude' => null],
        ]);

        $this->assertStringContainsString('STICKER', $result->promptText());
    }

    public function test_payment_receipt_image_is_classified_and_instructed(): void
    {
        config()->set('ai.media.vision.enabled', true);

        $vision = Mockery::mock(ImageAnalyzerInterface::class);
        $vision->shouldReceive('analyze')->once()->andReturn([
            'category' => 'comprobante_pago',
            'summary' => 'transferencia bancaria por Bs. 150',
        ]);

        $resolver = new IncomingMediaResolver(
            $this->mediaService(['contents' => 'IMG', 'mime' => 'image/jpeg']),
            null,
            $vision
        );

        $result = $resolver->resolve([
            ['kind' => 'image', 'url' => 'https://cw.test/r.jpg', 'latitude' => null, 'longitude' => null],
        ]);

        $this->assertStringContainsString('COMPROBANTE DE PAGO', $result->promptText());
        $this->assertStringContainsString('NO des el pago por confirmado', $result->promptText());
    }

    public function test_image_without_vision_uses_generic_instruction(): void
    {
        config()->set('ai.media.vision.enabled', false);

        $resolver = new IncomingMediaResolver($this->mediaService(['contents' => 'IMG', 'mime' => 'image/jpeg']), null, null);

        $result = $resolver->resolve([
            ['kind' => 'image', 'url' => 'https://cw.test/r.jpg', 'latitude' => null, 'longitude' => null],
        ]);

        $this->assertStringContainsString('comprobante de pago o un diseño', $result->promptText());
    }

    public function test_location_produces_maps_link_segment(): void
    {
        $resolver = new IncomingMediaResolver($this->mediaService(), null, null);

        $result = $resolver->resolve([
            ['kind' => 'location', 'url' => null, 'latitude' => -21.5, 'longitude' => -64.75],
        ]);

        $this->assertStringContainsString('https://maps.google.com/?q=-21.5,-64.75', $result->promptText());
        $this->assertStringContainsString('COMPARTIÓ SU UBICACIÓN', $result->promptText());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
