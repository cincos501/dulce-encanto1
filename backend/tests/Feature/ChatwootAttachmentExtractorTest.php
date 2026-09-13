<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\ChatwootAttachmentExtractor;
use Tests\TestCase;

class ChatwootAttachmentExtractorTest extends TestCase
{
    public function test_extracts_audio_attachment(): void
    {
        $refs = ChatwootAttachmentExtractor::fromPayload([
            'content' => '',
            'attachments' => [
                ['file_type' => 'audio', 'data_url' => 'https://cw.test/storage/audio/9.ogg'],
            ],
        ]);

        $this->assertCount(1, $refs);
        $this->assertSame('audio', $refs[0]['kind']);
        $this->assertSame('https://cw.test/storage/audio/9.ogg', $refs[0]['url']);
        $this->assertSame('ogg', $refs[0]['extension']);
    }

    public function test_webp_image_without_caption_is_detected_as_sticker(): void
    {
        $refs = ChatwootAttachmentExtractor::fromPayload([
            'content' => '',
            'attachments' => [
                ['file_type' => 'image', 'data_url' => 'https://cw.test/s/sticker.webp'],
            ],
        ]);

        $this->assertSame('sticker', $refs[0]['kind']);
    }

    public function test_webp_image_with_caption_is_a_regular_image(): void
    {
        $refs = ChatwootAttachmentExtractor::fromPayload([
            'content' => 'miren mi torta',
            'attachments' => [
                ['file_type' => 'image', 'data_url' => 'https://cw.test/s/pic.webp'],
            ],
        ]);

        $this->assertSame('image', $refs[0]['kind']);
    }

    public function test_jpeg_image_is_always_an_image(): void
    {
        $refs = ChatwootAttachmentExtractor::fromPayload([
            'content' => '',
            'attachments' => [
                ['file_type' => 'image', 'data_url' => 'https://cw.test/s/photo.jpg'],
            ],
        ]);

        $this->assertSame('image', $refs[0]['kind']);
    }

    public function test_extracts_location_from_attachment(): void
    {
        $refs = ChatwootAttachmentExtractor::fromPayload([
            'content' => '',
            'attachments' => [
                ['file_type' => 'location', 'coordinates_lat' => -21.53, 'coordinates_long' => -64.72],
            ],
        ]);

        $this->assertSame('location', $refs[0]['kind']);
        $this->assertEqualsWithDelta(-21.53, $refs[0]['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-64.72, $refs[0]['longitude'], 0.0001);
    }

    public function test_relative_url_is_prefixed_with_chatwoot_base_url(): void
    {
        config()->set('chatwoot.url', 'https://chat.example.com/');

        $refs = ChatwootAttachmentExtractor::fromPayload([
            'content' => '',
            'attachments' => [
                ['file_type' => 'audio', 'data_url' => '/rails/active_storage/blobs/abc/voice.oga'],
            ],
        ]);

        $this->assertSame('https://chat.example.com/rails/active_storage/blobs/abc/voice.oga', $refs[0]['url']);
    }

    public function test_no_attachments_returns_empty_array(): void
    {
        $this->assertSame([], ChatwootAttachmentExtractor::fromPayload(['content' => 'hola']));
    }
}
