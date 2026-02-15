@extends('layouts.docs')

@section('title', 'DST and Time Zones')

@section('content')
    <div class="docs-prose">
        <h1>DST and Time Zones</h1>

        <p>
            The <code>time-zone</code> prop controls how the browser interprets and positions events.
            Recurrence expansion is designed to preserve wall clock times across DST changes.
        </p>

        <h2>Demo</h2>

        <div class="not-prose overflow-hidden rounded-lg border border-docs-border bg-white p-4">
            <livewire:docs-demo-calendar
                view="timeGridWeek"
                initial-date="2026-03-08"
                first-day="0"
                today="2026-03-08"
                time-zone="America/New_York"
            />
        </div>
    </div>
@endsection
