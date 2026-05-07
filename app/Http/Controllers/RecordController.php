<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecordRequest;
use App\Http\Requests\UpdateRecordRequest;
use App\Repositories\RecordRepository;
use App\Services\ImageStorageService;
use App\Support\DateRecord;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RecordController extends Controller
{
    public function create(Request $request, RecordRepository $repository)
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

        $existingRecord = $repository->find($date);
        $record = $existingRecord ?? DateRecord::empty($date);
        $hasRecord = $existingRecord !== null;

        return view('records.form', compact('record', 'date', 'hasRecord'));
    }

    public function show(string $date, RecordRepository $repository): JsonResponse
    {
        $record = $repository->find($date);

        if ($record === null) {
            return response()->json(DateRecord::empty($date));
        }

        return response()->json($record);
    }

    public function range(Request $request, RecordRepository $repository): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
        ]);

        return response()->json([
            'items' => $repository->range($validated['start'], $validated['end']),
        ]);
    }

    public function search(Request $request, RecordRepository $repository): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d'],
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'in:health,study,ramblings,calendar_note'],
        ]);

        $start = $validated['start'] ?? null;
        $end = $validated['end'] ?? null;
        $hasDateRange = is_string($start) && $start !== '' && is_string($end) && $end !== '';
        if ($hasDateRange && $start > $end) {
            return response()->json([
                'message' => 'The end date must be on or after the start date.',
                'errors' => ['end' => ['The end date must be on or after the start date.']],
            ], 422);
        }

        $effectiveStart = $hasDateRange ? $start : '1900-01-01';
        $effectiveEnd = $hasDateRange ? $end : '2999-12-31';

        $keyword = trim((string) ($validated['q'] ?? ''));
        if ($keyword === '') {
            return response()->json([
                'items' => $repository->range($effectiveStart, $effectiveEnd),
            ]);
        }

        return response()->json([
            'items' => $repository->search(
                $keyword,
                $effectiveStart,
                $effectiveEnd,
                $validated['sections'] ?? []
            ),
        ]);
    }

    public function store(
        StoreRecordRequest $request,
        RecordRepository $repository,
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
            'level' => $data['level'] ?? 0,
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
        RecordRepository $repository,
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
            'level' => $data['level'] ?? ($existing['level'] ?? 0),
            'calendar_note' => $data['calendar_note'] ?? ($existing['calendar_note'] ?? null),
            'ramblings' => $data['ramblings'] ?? ($existing['ramblings'] ?? null),
            'health' => $data['health'] ?? [],
            'study' => $data['study'] ?? [],
            'images' => $images,
        ]);

        return redirect()->route('records.create', ['date' => $date])->with('status', "Record for {$date} updated.");
    }

    public function destroy(
        string $date,
        RecordRepository $repository,
        ImageStorageService $imageStorageService
    ): RedirectResponse
    {
        $record = $repository->find($date);
        if ($record === null) {
            return redirect()
                ->route('records.create', ['date' => $date])
                ->with('status', "No record found for {$date}.");
        }

        $paths = [];
        foreach (($record['images'] ?? []) as $image) {
            if (is_array($image) && isset($image['path']) && is_string($image['path']) && $image['path'] !== '') {
                $paths[] = $image['path'];
            }
        }

        $imageStorageService->deletePaths($paths);
        $repository->delete($date);

        return redirect()->route('calendar.index')->with('status', "Record for {$date} deleted.");
    }
}
