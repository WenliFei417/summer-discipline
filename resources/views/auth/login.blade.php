@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-sm space-y-6">
        <div>
            <h1 class="text-2xl font-semibold">Sign in</h1>
            <p class="mt-1 text-sm text-slate-500">You need an account to add or edit records. Viewing stays public.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-5">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Password</label>
                <input type="password" name="password" required autocomplete="current-password" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Remember me on this device
            </label>

            <button type="submit" class="w-full rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                Sign in
            </button>
        </form>

        <p class="text-center text-sm text-slate-500">
            <a href="{{ route('calendar.index') }}" class="text-slate-700 underline">Back to calendar</a>
        </p>
    </div>
@endsection
