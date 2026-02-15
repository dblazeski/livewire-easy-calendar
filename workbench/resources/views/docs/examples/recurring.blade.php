@extends('layouts.docs')

@section('title', 'Recurring Events Example')

@section('content')
    <div class="docs-prose">
        <h1>Recurring Events</h1>

        <p>
            The demo dataset includes a daily recurrence with an exclusion date.
            Open May 2026 and look for the recurring standup across multiple days.
        </p>

        <div class="not-prose overflow-hidden rounded-lg border border-docs-border bg-white p-4">
            <livewire:docs-demo-calendar
                view="month"
                initial-date="2026-05-01"
                first-day="0"
                today="2026-05-14"
                time-zone="UTC"
            />
        </div>
    </div>
@endsection
