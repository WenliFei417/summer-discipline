<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageStorageService
{
    /**
     * @return array{url: string, path: string}
     */
    public function upload(string $date, UploadedFile $file): array
    {
        $disk = env('IMAGE_DISK', config('filesystems.default', 'public'));
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $name = uniqid('img_', true).'.'.$ext;
        $path = "records/{$date}/{$name}";

        $stream = fopen($file->getRealPath(), 'r');
        Storage::disk($disk)->put($path, $stream, 'public');
        if (is_resource($stream)) {
            fclose($stream);
        }

        return [
            'url' => Storage::disk($disk)->url($path),
            'path' => $path,
        ];
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function deletePaths(array $paths): void
    {
        if ($paths === []) {
            return;
        }

        $disk = env('IMAGE_DISK', config('filesystems.default', 'public'));
        $normalized = array_values(array_filter($paths, fn (mixed $path): bool => is_string($path) && $path !== ''));

        foreach ($normalized as $path) {
            Storage::disk($disk)->delete($path);
        }
    }
}
