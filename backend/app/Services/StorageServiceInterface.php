<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;

interface StorageServiceInterface
{
    /**
     * Upload a file to a specific folder in the bucket and return the path.
     */
    public function upload(string $folder, UploadedFile $file): string;

    /**
     * Delete a file by its relative path from the storage bucket.
     */
    public function delete(string $path): bool;

    /**
     * Generate/retrieve the public URL for a given relative file path.
     */
    public function url(string $path): string;
}
