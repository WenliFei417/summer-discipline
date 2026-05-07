<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'record_date' => ['required', 'date_format:Y-m-d'],
            'calendar_note' => ['nullable', 'string', 'max:80'],
            'ramblings' => ['nullable', 'string', 'max:10000'],
            'health.workout' => ['nullable', 'string'],
            'health.diet' => ['nullable', 'string'],
            'health.sleep' => ['nullable', 'string'],
            'health.rating' => ['nullable', 'integer', 'between:1,5'],
            'study.leetcode' => ['nullable', 'string'],
            'study.system_design' => ['nullable', 'string'],
            'study.courses' => ['nullable', 'string'],
            'study.rating' => ['nullable', 'integer', 'between:1,5'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
