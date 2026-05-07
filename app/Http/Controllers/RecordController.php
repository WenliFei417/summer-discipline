<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecordRequest;
use App\Http\Requests\UpdateRecordRequest;
use App\Repositories\RecordFileRepository;
use App\Services\ImageStorageService;
use App\Support\DateRecord;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RecordController extends Controller
{
    public function create(Request $request, RecordFileRepository $repository)
    {
        $today = Carbon::today()->format('Y-m-d');
        $raw = $request->query('date');
        $date = $today;

        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw !== ''
                && Validator::make(['date' => $raw], ['date' => 'date_format:Y-m-d'])->passes()) {
                $date = $raw;
            }
        }

        $record = $repository->findOrEmpty($date);

        return view('records.form', compact('record', 'date'));
    }

    public function show(string $date, RecordFileRepository $repository): JsonResponse
    {
        $record = $repository->find($date);

        if ($record === null) {
            return response()->json(DateRecord::empty($date));
        }

        return response()->json($record);
    }

    public function range(Request $request, RecordFileRepository $repository): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
        ]);

        return response()->json([
            'items' => $repository->range($validated['start'], $validated['end']),
        ]);
    }

    public function store(
        StoreRecordRequest $request,
        RecordFileRepository $repository,
        ImageStorageService $imageStorageService
    ): RedirectResponse {
        $data = $request->validated();
        $date = $data['record_date'];
        $existing = $repository->find($date) ?? DateRecord::empty($date);
        $images = $existing['images'] ?? [];

        foreach ($request->file('images', []) as $file) {
            $uploaded = $imageStorageService->upload($date, $file);
            $images[] = [
                'url' => $uploaded['url'],
                'path' => $uploaded['path'],
                'caption' => null,
                'created_at' => now()->toIso8601String(),
            ];
        }

        $repository->save($date, [
            'calendar_note' => $data['calendar_note'] ?? null,
            'ramblings' => $data['ramblings'] ?? null,
            'health' => $data['health'] ?? [],
            'study' => $data['study'] ?? [],
            'images' => $images,
        ]);

        return redirect()->route('calendar.index')->with('status', "Record for {$date} saved.");
    }

    public function update(
        string $date,
        UpdateRecordRequest $request,
        RecordFileRepository $repository,
        ImageStorageService $imageStorageService
    ): RedirectResponse {
        $data = $request->validated();
        $existing = $repository->find($date) ?? DateRecord::empty($date);
        $images = $existing['images'] ?? [];

        foreach ($request->file('images', []) as $file) {
            $uploaded = $imageStorageService->upload($date, $file);
            $images[] = [
                'url' => $uploaded['url'],
                'path' => $uploaded['path'],
                'caption' => null,
                'created_at' => now()->toIso8601String(),
            ];
        }

        $repository->save($date, [
            'calendar_note' => $data['calendar_note'] ?? ($existing['calendar_note'] ?? null),
            'ramblings' => $data['ramblings'] ?? ($existing['ramblings'] ?? null),
            'health' => $data['health'] ?? [],
            'study' => $data['study'] ?? [],
            'images' => $images,
        ]);

        return redirect()->route('records.create', ['date' => $date])->with('status', "Record for {$date} updated.");
    }
}
