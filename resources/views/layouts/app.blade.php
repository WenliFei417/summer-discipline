<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Summer Discipline' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important;}</style>
</head>
<body class="bg-slate-50 text-slate-900">
    <div class="mx-auto min-h-screen max-w-6xl px-4 py-8">
        <div class="mb-6 flex flex-wrap items-center justify-end gap-3 text-sm">
            @auth
                <span class="max-w-[12rem] truncate text-slate-500" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="font-medium text-slate-900 underline underline-offset-2 hover:text-slate-600">Sign out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="font-medium text-slate-900 underline underline-offset-2 hover:text-slate-600">Sign in to edit</a>
            @endauth
        </div>
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif
        @yield('content')
    </div>
</body>
</html>
