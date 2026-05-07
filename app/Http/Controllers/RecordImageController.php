<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRecordImageRequest;
use App\Repositories\RecordFileRepository;
use App\Services\ImageStorageService;
use App\Support\DateRecord;
use Illuminate\Http\JsonResponse;

class RecordImageController extends Controller
{
    public function store(
        string $date,
        UploadRecordImageRequest $request,
        RecordFileRepository $repository,
        ImageStorageService $imageStorageService
    ): JsonResponse {
        $uploaded = $imageStorageService->upload($date, $request->file('image'));
        $record = $repository->find($date) ?? DateRecord::empty($date);
        $images = $record['images'] ?? [];

        $images[] = [
            'url' => $uploaded['url'],
            'path' => $uploaded['path'],
            'caption' => $request->validated('caption'),
            'created_at' => now()->toIso8601String(),
        ];

        $record = $repository->save($date, [
            'calendar_note' => $record['calendar_note'] ?? null,
            'ramblings' => $record['ramblings'] ?? null,
            'health' => $record['health'] ?? [],
            'study' => $record['study'] ?? [],
            'images' => $images,
        ]);

        return response()->json([
            'message' => 'Image uploaded.',
            'record' => $record,
        ]);
    }
}
