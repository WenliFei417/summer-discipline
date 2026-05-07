<?php

namespace App\Repositories;

use App\Models\Record;
use App\Support\DateRecord;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class RecordRepository
{
    public function find(string $date): ?array
    {
        if (! $this->recordsTableExists()) {
            return null;
        }

        $record = Record::with('images')
            ->whereDate('record_date', $date)
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->toPayload($record);
    }

    public function findOrEmpty(string $date): array
    {
        return $this->find($date) ?? DateRecord::empty($date);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function save(string $date, array $payload): array
    {
        $normalized = $this->normalizePayload($date, $payload);

        if (! $this->recordsTableExists()) {
            return $normalized;
        }

        $record = Record::updateOrCreate(
            ['record_date' => $date],
            [
                'calendar_note' => $normalized['calendar_note'],
                'ramblings' => $normalized['ramblings'],
                'health' => $normalized['health'],
                'study' => $normalized['study'],
            ]
        );

        $record->images()->delete();
        foreach ($normalized['images'] as $image) {
            $record->images()->create([
                'url' => (string) Arr::get($image, 'url', ''),
                'path' => (string) Arr::get($image, 'path', ''),
                'caption' => Arr::get($image, 'caption'),
                'created_at' => Arr::get($image, 'created_at', now()->toIso8601String()),
            ]);
        }

        return $normalized;
    }

    public function delete(string $date): bool
    {
        if (! $this->recordsTableExists()) {
            return false;
        }

        $record = Record::query()->whereDate('record_date', $date)->first();
        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function range(string $start, string $end): array
    {
        if (! $this->recordsTableExists()) {
            return [];
        }

        $startDate = Carbon::createFromFormat('Y-m-d', $start)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $end)->startOfDay();

        if ($startDate->greaterThan($endDate)) {
            throw new InvalidArgumentException('Start date must be before end date.');
        }

        return Record::with('images')
            ->whereBetween('record_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('record_date')
            ->get()
            ->map(fn (Record $record): array => $this->toPayload($record))
            ->values()
            ->all();
    }

    /**
     * @return array<string, array{level:int, health_level:int, study_level:int, calendar_note:string|null}>
     */
    public function monthCardMap(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $records = collect();
        if ($this->recordsTableExists()) {
            $records = Record::query()
                ->whereBetween('record_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->keyBy(fn (Record $record): string => $record->record_date->toDateString());
        }

        $daysInMonth = $start->daysInMonth;
        $map = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->toDateString();
            $payload = null;
            $record = $records->get($date);
            if ($record instanceof Record) {
                $payload = $this->toPayload($record);
            }

            $map[$date] = [
                'level' => $this->toLevel($payload),
                'health_level' => $this->toModuleLevel($payload, 'health'),
                'study_level' => $this->toModuleLevel($payload, 'study'),
                'calendar_note' => is_array($payload) ? (Arr::get($payload, 'calendar_note') ?: null) : null,
            ];
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(string $date, array $payload): array
    {
        $base = DateRecord::empty($date);
        $base['calendar_note'] = Arr::get($payload, 'calendar_note');
        $base['ramblings'] = Arr::get($payload, 'ramblings');
        $base['health'] = array_merge($base['health'], Arr::get($payload, 'health', []));
        $base['study'] = $this->migrateStudySection(
            array_merge($base['study'], Arr::get($payload, 'study', []))
        );
        $base['images'] = Arr::get($payload, 'images', []);
        $base['updated_at'] = now()->toIso8601String();

        return $base;
    }

    /**
     * Renamed study.other → study.courses; migrate legacy JSON.
     *
     * @param  array<string, mixed>  $study
     * @return array<string, mixed>
     */
    private function migrateStudySection(array $study): array
    {
        $courses = Arr::get($study, 'courses');
        $legacyOther = Arr::get($study, 'other');

        $coursesMissing = $courses === null || $courses === '';
        if ($coursesMissing && $legacyOther !== null && $legacyOther !== '') {
            $study['courses'] = $legacyOther;
        }

        unset($study['other']);

        return $study;
    }

    /**
     * @param  array<string, mixed>|null  $record
     */
    private function toLevel(?array $record): int
    {
        if ($record === null) {
            return 0;
        }

        $health = Arr::get($record, 'health.rating');
        $study = Arr::get($record, 'study.rating');

        if ($health === null && $study === null) {
            return 1;
        }

        if ($health === null) {
            return (int) $study;
        }

        if ($study === null) {
            return (int) $health;
        }

        return (int) ceil((((int) $health) + ((int) $study)) / 2);
    }

    /**
     * @param  array<string, mixed>|null  $record
     */
    private function toModuleLevel(?array $record, string $module): int
    {
        if ($record === null) {
            return 0;
        }

        $rating = Arr::get($record, "{$module}.rating");

        return $rating === null ? 0 : (int) $rating;
    }

    /**
     * @return array<string, mixed>
     */
    private function toPayload(Record $record): array
    {
        $study = is_array($record->study) ? $this->migrateStudySection($record->study) : [];

        return [
            'date' => $record->record_date->toDateString(),
            'calendar_note' => $record->calendar_note,
            'ramblings' => $record->ramblings,
            'health' => is_array($record->health) ? $record->health : [],
            'study' => $study,
            'images' => $record->images
                ->map(fn ($image): array => [
                    'url' => $image->url,
                    'path' => $image->path,
                    'caption' => $image->caption,
                    'created_at' => optional($image->created_at)->toIso8601String(),
                ])
                ->all(),
            'updated_at' => optional($record->updated_at)->toIso8601String(),
        ];
    }

    private function recordsTableExists(): bool
    {
        return Schema::hasTable('records');
    }
}
