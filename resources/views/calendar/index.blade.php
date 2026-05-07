@extends('layouts.app')

@php
    $title = 'Calendar';
    $monthStart = $month->copy()->startOfMonth();
    $monthEnd = $month->copy()->endOfMonth();
    $calendarStart = $monthStart->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $calendarEnd = $monthEnd->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $cursor = $calendarStart->copy();
    $weeks = [];
    while ($cursor->lessThanOrEqualTo($calendarEnd)) {
        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $week[] = $cursor->copy();
            $cursor->addDay();
        }
        $weeks[] = $week;
    }
@endphp

@section('content')
    <div
        x-data="calendarPage('{{ $month->format('Y-m') }}', @js($cardMap))"
        class="space-y-6"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Discipline Calendar</h1>
                <p class="text-sm text-slate-500">Click a date to view notes. Use quick search for recent records.</p>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="loadQuickRange('week')"
                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                    This Week
                </button>
                <button
                    type="button"
                    @click="loadQuickRange('month')"
                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                    This Month
                </button>
                <div class="relative">
                    <button
                        type="button"
                        @click="showSearchPanel = !showSearchPanel; showSectionMenu = false"
                        class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                    >
                        Search
                    </button>
                    <div
                        x-show="showSearchPanel"
                        x-cloak
                        @click.outside="showSearchPanel = false; showSectionMenu = false"
                        class="absolute left-0 z-30 mt-2 w-[22rem] max-w-[calc(100vw-2rem)] rounded-lg border border-slate-200 bg-white p-3 shadow-lg"
                    >
                        <div class="space-y-2">
                            <input
                                x-model="searchKeyword"
                                type="text"
                                placeholder="Keyword (optional)"
                                @keydown.enter.prevent="searchRecords()"
                                class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-700 placeholder:text-slate-400"
                            >
                            <div class="grid grid-cols-2 gap-2">
                                <input x-model="searchStart" type="date" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-700">
                                <input x-model="searchEnd" type="date" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-700">
                            </div>
                            <div class="relative">
                                <button
                                    type="button"
                                    @click="showSectionMenu = !showSectionMenu"
                                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-left text-xs text-slate-700 hover:bg-slate-100"
                                >
                                    Sections (<span x-text="searchSections.length"></span>)
                                </button>
                                <div
                                    x-show="showSectionMenu"
                                    x-cloak
                                    class="mt-1 rounded-md border border-slate-200 bg-white p-2"
                                >
                                    <label class="mb-1 flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" value="health" x-model="searchSections" class="h-3 w-3">Health</label>
                                    <label class="mb-1 flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" value="study" x-model="searchSections" class="h-3 w-3">Study</label>
                                    <label class="mb-1 flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" value="ramblings" x-model="searchSections" class="h-3 w-3">Ramblings</label>
                                    <label class="flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" value="calendar_note" x-model="searchSections" class="h-3 w-3">Note</label>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="searchRecords()"
                                class="w-full rounded-md bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700"
                            >
                                Apply Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                @auth
                    <a
                        href="{{ route('records.create') }}"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                    >
                        New Record
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                        title="Sign in to create or edit records"
                    >
                        Sign in to edit
                    </a>
                @endauth
            </div>
        </div>

        <div class="grid gap-6">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <a href="{{ route('calendar.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="shrink-0 text-sm text-slate-600 hover:text-slate-900">&larr; Prev</a>
                    <div class="relative flex flex-1 justify-center">
                        <button
                            type="button"
                            class="rounded px-2 py-1 text-lg font-semibold hover:bg-slate-100"
                            @click="showMonthPicker = !showMonthPicker"
                        >
                            {{ $month->format('F Y') }}
                        </button>
                        <div
                            x-show="showMonthPicker"
                            x-cloak
                            x-transition
                            @click.outside="showMonthPicker = false"
                            class="absolute left-1/2 top-full z-30 mt-2 w-64 max-w-[calc(100vw-2rem)] -translate-x-1/2 rounded-lg border border-slate-200 bg-white p-3 shadow-lg"
                        >
                            <label class="mb-1 block text-xs text-slate-500">Go to month</label>
                            <input
                                type="month"
                                x-model="jumpMonthValue"
                                class="w-full rounded border border-slate-300 px-2 py-2 text-sm"
                            >
                            <button
                                type="button"
                                class="mt-3 w-full rounded bg-slate-900 py-2 text-xs font-medium text-white hover:bg-slate-700"
                                @click="goToMonth()"
                            >
                                Go
                            </button>
                        </div>
                    </div>
                    <a href="{{ route('calendar.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="shrink-0 text-sm text-slate-600 hover:text-slate-900">Next &rarr;</a>
                </div>
                <div class="mb-3 flex items-center gap-2 text-xs text-slate-600">
                    <span>Color by:</span>
                    <select x-model="calendarMetric" class="rounded border border-slate-300 px-2 py-1 text-xs">
                        <option value="level">Level</option>
                        <option value="health_level">Health</option>
                        <option value="study_level">Study</option>
                    </select>
                </div>

                <div class="grid grid-cols-7 gap-2 text-xs font-medium text-slate-500">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $label)
                        <div class="px-2 py-1">{{ $label }}</div>
                    @endforeach
                </div>

                <div class="mt-2 grid gap-2">
                    @foreach ($weeks as $week)
                        <div class="grid grid-cols-7 gap-2">
                            @foreach ($week as $day)
                                @php
                                    $date = $day->toDateString();
                                    $card = $cardMap[$date] ?? ['level' => 0, 'calendar_note' => null];
                                    $calendarNote = $card['calendar_note'];
                                    $isCurrentMonth = $day->month === $month->month;
                                @endphp
                                <button
                                    type="button"
                                    @click="openDay('{{ $date }}')"
                                    @if ($calendarNote) title="{{ $calendarNote }}" @endif
                                    :style="dayStyle('{{ $date }}')"
                                    class="flex h-24 flex-col items-start gap-1 rounded-lg border pb-2 pl-2 pr-2 pt-2 text-left transition hover:shadow {{ $isCurrentMonth ? '' : 'opacity-50' }}"
                                >
                                    <div class="shrink-0 text-lg font-semibold leading-none text-slate-900">{{ $day->day }}</div>
                                    @if ($calendarNote)
                                        <div class="max-h-[3.5rem] min-h-0 w-full overflow-hidden break-words text-left text-sm leading-snug text-slate-600">
                                            {{ $calendarNote }}
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold">Range Results</h3>
                <p class="text-xs text-slate-500" x-show="hasSearched" x-text="searchMetaText"></p>
            </div>

            <template x-if="hasSearched && rangeItems.length === 0">
                <p class="text-sm text-slate-500">No matching records.</p>
            </template>

            <template x-if="!hasSearched">
                <p class="text-sm text-slate-500">Choose a date range and click Search.</p>
            </template>

            <div x-show="rangeItems.length > 0" class="space-y-3">
                <template x-for="item in rangeItems" :key="item.date">
                    <div
                        role="button"
                        tabindex="0"
                        class="cursor-pointer rounded-lg border border-slate-200 p-3 text-sm outline-none transition hover:border-slate-300 hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-slate-400"
                        @click="openDay(item.date)"
                        @keydown.enter.prevent="openDay(item.date)"
                    >
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <span class="shrink-0 font-semibold text-slate-900" x-text="monthDayLabel(item.date)"></span>
                                    <span class="min-w-0 truncate text-xs text-slate-600" x-show="item.calendar_note" x-text="item.calendar_note"></span>
                                </div>
                                <div
                                    class="mt-2 flex w-full flex-wrap items-baseline gap-x-2 gap-y-1 text-left text-xs text-slate-700"
                                    x-show="item.ramblings"
                                >
                                    <span class="shrink-0 font-medium text-slate-900">Ramblings:</span>
                                    <span class="min-w-0 whitespace-pre-wrap break-words leading-snug" x-text="item.ramblings"></span>
                                </div>
                                <div class="mt-2 grid gap-x-8 gap-y-1 text-xs text-slate-700 md:grid-cols-2">
                                    <div class="space-y-1">
                                        <p class="font-semibold text-slate-800">
                                            Health:
                                            <span class="font-normal text-slate-600" x-text="item.health?.rating != null && item.health?.rating !== '' ? item.health.rating : '—'"></span>
                                        </p>
                                        <p x-show="item.health?.workout"><span class="font-medium">Workout:</span> <span x-text="item.health?.workout"></span></p>
                                        <p x-show="item.health?.diet"><span class="font-medium">Diet:</span> <span x-text="item.health?.diet"></span></p>
                                        <p x-show="item.health?.sleep"><span class="font-medium">Sleep:</span> <span x-text="item.health?.sleep"></span></p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-semibold text-slate-800">
                                            Study:
                                            <span class="font-normal text-slate-600" x-text="item.study?.rating != null && item.study?.rating !== '' ? item.study.rating : '—'"></span>
                                        </p>
                                        <p x-show="item.study?.leetcode"><span class="font-medium">LeetCode:</span> <span x-text="item.study?.leetcode"></span></p>
                                        <p x-show="item.study?.system_design"><span class="font-medium">System design:</span> <span x-text="item.study?.system_design"></span></p>
                                        <p x-show="item.study?.courses || item.study?.other"><span class="font-medium">Courses:</span> <span x-text="item.study?.courses || item.study?.other"></span></p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid w-full grid-cols-3 gap-2 md:w-48" x-show="(item.images || []).length > 0" @click.stop>
                                <template x-for="(img, idx) in (item.images || []).slice(0, 3)" :key="idx">
                                    <button type="button" @click.stop="openImagePreview(img.url)" class="block overflow-hidden rounded border border-slate-200">
                                        <img :src="img.url" class="h-16 w-full object-cover" alt="range image">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="showModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-2xl rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-xl font-semibold" x-text="selected?.date"></h3>
                        <p class="text-xs text-slate-500">Day detail</p>
                    </div>
                    <button
                        type="button"
                        class="h-8 w-8 rounded-full text-lg leading-8 text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                        @click="showModal = false"
                        aria-label="Close"
                        title="Close"
                    >
                        &times;
                    </button>
                </div>
                <div class="mt-4 space-y-3">
                    <div class="rounded border border-slate-200 p-3 text-sm">
                        <div class="flex items-start gap-2 text-xs text-slate-700">
                            <span class="font-semibold text-sm text-slate-900">Note</span>
                            <span class="break-words" x-text="selected?.calendar_note || '-'"></span>
                        </div>
                    </div>
                    <div class="rounded border border-slate-200 p-3 text-sm" x-show="selected?.ramblings">
                        <p class="font-semibold text-sm text-slate-900">Ramblings</p>
                        <p class="mt-1 whitespace-pre-wrap text-xs text-slate-700" x-text="selected?.ramblings"></p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded border border-slate-200 p-3 text-sm">
                            <h4 class="font-semibold">Health</h4>
                            <p class="mt-2 text-xs" x-show="selected?.health?.workout">Workout: <span x-text="selected?.health?.workout"></span></p>
                            <p class="text-xs" x-show="selected?.health?.diet">Diet: <span x-text="selected?.health?.diet"></span></p>
                            <p class="text-xs" x-show="selected?.health?.sleep">Sleep: <span x-text="selected?.health?.sleep"></span></p>
                            <p class="mt-2 text-xs" x-show="selected?.health?.rating">Rating: <span x-text="selected?.health?.rating"></span></p>
                            <p class="mt-2 text-xs text-slate-500" x-show="!selected?.health?.workout && !selected?.health?.diet && !selected?.health?.sleep && !selected?.health?.rating">No health details.</p>
                        </div>
                        <div class="rounded border border-slate-200 p-3 text-sm">
                            <h4 class="font-semibold">Study</h4>
                            <p class="mt-2 text-xs" x-show="selected?.study?.leetcode">LeetCode: <span x-text="selected?.study?.leetcode"></span></p>
                            <p class="text-xs" x-show="selected?.study?.system_design">System Design: <span x-text="selected?.study?.system_design"></span></p>
                            <p class="text-xs" x-show="selected?.study?.courses || selected?.study?.other">Courses: <span x-text="selected?.study?.courses || selected?.study?.other"></span></p>
                            <p class="mt-2 text-xs" x-show="selected?.study?.rating">Rating: <span x-text="selected?.study?.rating"></span></p>
                            <p class="mt-2 text-xs text-slate-500" x-show="!selected?.study?.leetcode && !selected?.study?.system_design && !selected?.study?.courses && !selected?.study?.other && !selected?.study?.rating">No study details.</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <h4 class="text-sm font-semibold">Images</h4>
                    <div class="mt-2 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <template x-for="(img, idx) in (selected?.images || [])" :key="idx">
                            <button type="button" @click="openImagePreview(img.url)" class="block overflow-hidden rounded border border-slate-200">
                                <img :src="img.url" class="h-20 w-full object-cover" alt="daily image">
                            </button>
                        </template>
                    </div>
                    <p x-show="(selected?.images || []).length === 0" class="text-xs text-slate-500">No images.</p>
                </div>
                @auth
                    <div class="mt-4 text-right">
                        <a :href="`/records/create?date=${selected?.date}`" class="text-sm font-medium text-slate-900 underline">Edit this day</a>
                    </div>
                @endauth
            </div>
        </div>

        <div x-show="showImagePreview" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click="closeImagePreview()">
            <div class="relative max-h-[90vh] max-w-[90vw]" @click.stop>
                <button
                    type="button"
                    class="absolute -right-2 -top-2 h-8 w-8 rounded-full bg-white text-lg leading-8 text-slate-700 shadow hover:bg-slate-100"
                    @click="closeImagePreview()"
                    aria-label="Close image preview"
                    title="Close"
                >
                    &times;
                </button>
                <img :src="previewImageUrl" alt="preview image" class="max-h-[90vh] max-w-[90vw] rounded-lg object-contain shadow-2xl">
            </div>
        </div>
    </div>

    <script>
        function calendarPage(currentMonth, cardMap) {
            const today = new Date().toISOString().slice(0, 10);
            return {
                showModal: false,
                selected: null,
                showImagePreview: false,
                previewImageUrl: '',
                searchStart: '',
                searchEnd: '',
                searchKeyword: '',
                searchSections: ['health', 'study', 'ramblings', 'calendar_note'],
                rangeItems: [],
                hasSearched: false,
                searchMetaText: '',
                showSearchPanel: false,
                showSectionMenu: false,
                showMonthPicker: false,
                jumpMonthValue: currentMonth,
                calendarMetric: 'level',
                cardMap: cardMap || {},
                palette: {
                    0: 'background-color:#FFFFFF;border-color:#e2e8f0;',
                    1: 'background-color:#DDF0EE;border-color:#DDF0EE;',
                    2: 'background-color:#C8E5E4;border-color:#C8E5E4;',
                    3: 'background-color:#E3E2F0;border-color:#E3E2F0;',
                    4: 'background-color:#ECDAEA;border-color:#ECDAEA;',
                    5: 'background-color:#EAC8DF;border-color:#EAC8DF;'
                },
                toDateInput(dateObj) {
                    const y = dateObj.getFullYear();
                    const m = `${dateObj.getMonth() + 1}`.padStart(2, '0');
                    const d = `${dateObj.getDate()}`.padStart(2, '0');
                    return `${y}-${m}-${d}`;
                },
                setWeekRange() {
                    const now = new Date();
                    const day = now.getDay() || 7; // Monday=1 ... Sunday=7
                    const monday = new Date(now);
                    monday.setDate(now.getDate() - day + 1);
                    const sunday = new Date(monday);
                    sunday.setDate(monday.getDate() + 6);
                    this.searchStart = this.toDateInput(monday);
                    this.searchEnd = this.toDateInput(sunday);
                },
                setMonthRange() {
                    const now = new Date();
                    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                    this.searchStart = this.toDateInput(firstDay);
                    this.searchEnd = this.toDateInput(lastDay);
                },
                loadQuickRange(mode) {
                    if (mode === 'week') {
                        this.setWeekRange();
                    } else if (mode === 'month') {
                        this.setMonthRange();
                    }
                    this.searchRecords();
                },
                dayStyle(date) {
                    const card = this.cardMap?.[date] || {};
                    const metric = this.calendarMetric || 'level';
                    const level = Number(card?.[metric] ?? 0);
                    return this.palette[level] || this.palette[0];
                },
                goToMonth() {
                    if (!this.jumpMonthValue) {
                        return;
                    }
                    window.location.href = `{{ route('calendar.index') }}?month=${this.jumpMonthValue}`;
                },
                monthDayLabel(ymd) {
                    if (!ymd || typeof ymd !== 'string' || !ymd.includes('-')) {
                        return ymd;
                    }
                    const parts = ymd.split('-');
                    if (parts.length !== 3) {
                        return ymd;
                    }
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10);
                    const d = parseInt(parts[2], 10);
                    if (Number.isNaN(y) || Number.isNaN(m) || Number.isNaN(d)) {
                        return ymd;
                    }
                    const date = new Date(y, m - 1, d);
                    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
                },
                async openDay(date) {
                    const response = await fetch(`/records/${date}`);
                    this.selected = await response.json();
                    this.showModal = true;
                },
                openImagePreview(url) {
                    if (!url) {
                        return;
                    }
                    this.previewImageUrl = url;
                    this.showImagePreview = true;
                },
                closeImagePreview() {
                    this.showImagePreview = false;
                    this.previewImageUrl = '';
                },
                async searchRecords() {
                    const keyword = (this.searchKeyword || '').trim();

                    this.hasSearched = true;
                    this.showSearchPanel = false;
                    this.showSectionMenu = false;
                    const sectionLabel = (this.searchSections || []).join(', ') || 'all';
                    const hasDateRange = this.searchStart && this.searchEnd;
                    const dateText = hasDateRange ? `${this.searchStart} ~ ${this.searchEnd}` : 'All dates';
                    this.searchMetaText = keyword
                        ? `${dateText} | "${keyword}" in ${sectionLabel}`
                        : dateText;

                    const params = new URLSearchParams({
                        q: this.searchKeyword || '',
                    });
                    if (this.searchStart && this.searchEnd) {
                        params.set('start', this.searchStart);
                        params.set('end', this.searchEnd);
                    }
                    (this.searchSections || []).forEach((section) => {
                        params.append('sections[]', section);
                    });

                    const response = await fetch(`/records/search?${params.toString()}`);
                    const data = await response.json();
                    this.rangeItems = data.items || [];
                },
            };
        }
    </script>
@endsection
