<?php

namespace App\Support;

final class DateRecord
{
    /**
     * @return array<string, mixed>
     */
    public static function empty(string $date): array
    {
        return [
            'date' => $date,
            'level' => 0,
            'calendar_note' => null,
            'ramblings' => null,
            'health' => [
                'workout' => null,
                'diet' => null,
                'sleep' => null,
                'rating' => null,
            ],
            'study' => [
                'leetcode' => null,
                'system_design' => null,
                'courses' => null,
                'rating' => null,
            ],
            'images' => [],
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
