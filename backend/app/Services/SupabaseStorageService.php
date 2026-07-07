<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseStorageService implements StorageServiceInterface
{
    protected ?string $url;

    protected ?string $bucket;

    protected ?string $secretKey;

    public function __construct()
    {
        $this->url = config('supabase.url') ? (string) config('supabase.url') : null;
        $this->bucket = config('supabase.bucket') ? (string) config('supabase.bucket') : null;
        $this->secretKey = config('supabase.secret_key') ? (string) config('supabase.secret_key') : null;
    }

    /**
     * Upload a file to Supabase Storage.
     *
     * @throws \RuntimeException
     */
    public function upload(string $folder, UploadedFile $file): string
    {
        $this->validateConfig();

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString().'.'.($extension !== '' ? $extension : 'jpg');
        $path = trim($folder, '/').'/'.$filename;

        $endpoint = sprintf(
            '%s/storage/v1/object/%s/%s',
            rtrim($this->url, '/'),
            $this->bucket,
            $path
        );

        $response = Http::withHeaders([
            'apikey' => $this->secretKey,
            'Authorization' => 'Bearer '.$this->secretKey,
        ])
            ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
            ->post($endpoint);

        if ($response->failed()) {
            throw new \RuntimeException('Error al subir el archivo a Supabase Storage: '.($response->json('message') ?? $response->body()));
        }

        return $path;
    }

    /**
     * Delete a file from Supabase Storage.
     */
    public function delete(string $path): bool
    {
        $this->validateConfig();

        $endpoint = sprintf(
            '%s/storage/v1/object/%s/%s',
            rtrim($this->url, '/'),
            $this->bucket,
            $path
        );

        $response = Http::withHeaders([
            'apikey' => $this->secretKey,
            'Authorization' => 'Bearer '.$this->secretKey,
        ])
            ->delete($endpoint);

        // 404 means the file was already deleted/not found, which is successful for our intent
        if ($response->status() === 404) {
            return true;
        }

        return $response->successful();
    }

    /**
     * Generate the public URL for a file path.
     */
    public function url(string $path): string
    {
        $this->validateConfig();

        return sprintf(
            '%s/storage/v1/object/public/%s/%s',
            rtrim($this->url, '/'),
            $this->bucket,
            $path
        );
    }

    /**
     * Ensure Supabase configuration is loaded.
     *
     * @throws \RuntimeException
     */
    protected function validateConfig(): void
    {
        if (empty($this->url) || empty($this->bucket) || empty($this->secretKey)) {
            throw new \RuntimeException('Configuración de Supabase Storage incompleta. Verifique las variables de entorno SUPABASE_URL, SUPABASE_BUCKET y SUPABASE_SECRET_KEY.');
        }
    }
}
