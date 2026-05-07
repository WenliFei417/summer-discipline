<?php

namespace App\Repositories;

use App\Support\DateRecord;
use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class RecordFileRepository
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
    }

    public function find(string $date): ?array
    {
        $path = $this->pathForDate($date);

        if (! $this->files->exists($path)) {
            return null;
        }

        $raw = $this->files->get($path);
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded['study']) && is_array($decoded['study'])) {
            $decoded['study'] = $this->migrateStudySection($decoded['study']);
        }

        return $decoded;
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
        $path = $this->pathForDate($date);
        $dir = dirname($path);

        if (! $this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        $this->files->put($path, json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $normalized;
    }

    public function delete(string $date): bool
    {
        $path = $this->pathForDate($date);

        if (! $this->files->exists($path)) {
            return false;
        }

        return $this->files->delete($path);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function range(string $start, string $end): array
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $start)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $end)->startOfDay();

        if ($startDate->greaterThan($endDate)) {
            throw new InvalidArgumentException('Start date must be before end date.');
        }

        $results = [];
        $cursor = $startDate->copy();

        while ($cursor->lessThanOrEqualTo($endDate)) {
            $date = $cursor->toDateString();
            $record = $this->find($date);

            if ($record !== null) {
                $results[] = $record;
            }

            $cursor->addDay();
        }

        usort($results, fn (array $a, array $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        return $results;
    }

    /**
     * @return array<string, array{level:int, health_level:int, study_level:int, calendar_note:string|null}>
     */
    public function monthCardMap(int $year, int $month): array
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $map = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->toDateString();
            $record = $this->find($date);
            $map[$date] = [
                'level' => $this->toLevel($record),
                'health_level' => $this->toModuleLevel($record, 'health'),
                'study_level' => $this->toModuleLevel($record, 'study'),
                'calendar_note' => is_array($record) ? (Arr::get($record, 'calendar_note') ?: null) : null,
            ];
        }

        return $map;
    }

    private function pathForDate(string $date): string
    {
        $year = Carbon::createFromFormat('Y-m-d', $date)->format('Y');

        return storage_path("app/records/{$year}/{$date}.json");
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
}
