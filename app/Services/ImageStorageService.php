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
}
