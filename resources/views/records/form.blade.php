@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Record Form</h1>
                <p class="text-sm text-slate-500">Create or update one record for a specific date.</p>
            </div>
            <a href="{{ route('calendar.index') }}" class="text-sm text-slate-700 underline">Back to calendar</a>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('records.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-xl border border-slate-200 bg-white p-5">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Date</label>
                    <input type="date" name="record_date" value="{{ old('record_date', $date) }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Daily Level (0-5)</label>
                    <input
                        type="number"
                        name="level"
                        min="0"
                        max="5"
                        value="{{ old('level', data_get($record, 'level', 0)) }}"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                        placeholder="0 means no color on calendar"
                    >
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Calendar note (shown in day cell)</label>
                <input
                    type="text"
                    name="calendar_note"
                    maxlength="80"
                    value="{{ old('calendar_note', data_get($record, 'calendar_note')) }}"
                    class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Short memo for this date"
                >
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Ramblings</label>
                <input
                    type="text"
                    name="ramblings"
                    value="{{ old('ramblings', data_get($record, 'ramblings')) }}"
                    class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Free-form notes—feelings, small wins, moods, whatever happened today."
                >
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold">Health</h2>
                        <input type="number" name="health[rating]" min="1" max="5" value="{{ old('health.rating', data_get($record, 'health.rating')) }}" class="w-24 rounded border border-slate-300 px-2 py-1.5 text-sm" placeholder="1-5">
                    </div>
                    <input type="text" name="health[workout]" value="{{ old('health.workout', data_get($record, 'health.workout')) }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Workout note">
                    <input type="text" name="health[diet]" value="{{ old('health.diet', data_get($record, 'health.diet')) }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Diet note">
                    <input type="text" name="health[sleep]" value="{{ old('health.sleep', data_get($record, 'health.sleep')) }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Sleep note">
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold">Study</h2>
                        <input type="number" name="study[rating]" min="1" max="5" value="{{ old('study.rating', data_get($record, 'study.rating')) }}" class="w-24 rounded border border-slate-300 px-2 py-1.5 text-sm" placeholder="1-5">
                    </div>
                    <input type="text" name="study[leetcode]" value="{{ old('study.leetcode', data_get($record, 'study.leetcode')) }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="LeetCode note">
                    <input type="text" name="study[system_design]" value="{{ old('study.system_design', data_get($record, 'study.system_design')) }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="System design note">
                    <input type="text" name="study[courses]" value="{{ old('study.courses', data_get($record, 'study.courses', data_get($record, 'study.other'))) }}" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Courses, tutorials, etc.">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Upload images (optional)</label>
                <input type="file" name="images[]" accept="image/*" multiple class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>

            @if (! empty($record['images']))
                <div>
                    <p class="mb-2 text-sm font-medium">Existing Images</p>
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        @foreach ($record['images'] as $image)
                            <a href="{{ $image['url'] }}" target="_blank" class="block overflow-hidden rounded border border-slate-200">
                                <img src="{{ $image['url'] }}" alt="record image" class="h-20 w-full object-cover">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                Save Record
            </button>
        </form>

        @if (($hasRecord ?? false) === true)
            <form
                method="POST"
                action="{{ route('records.destroy', ['date' => $date]) }}"
                onsubmit="return confirm('Delete record for {{ $date }}? This cannot be undone.');"
                class="flex justify-end"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                    Delete This Day
                </button>
            </form>
        @endif
    </div>
@endsection
