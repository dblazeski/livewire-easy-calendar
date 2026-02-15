@extends('layouts.docs')

@section('title', 'Event Click')

@section('content')
    <div class="docs-prose">
        <h1>Event Click</h1>

        <p>
            Override <code>onEventClick(string $eventId, array $eventData)</code> to handle event clicks.
            In this package, event click is triggered for timed events in time grid views.
        </p>

        <pre><code>protected function onEventClick(string $eventId, array $eventData): void
{
    // $eventData includes id, title, start, end
}</code></pre>

        <div class="not-prose overflow-hidden rounded-lg border border-docs-border bg-white p-4">
            <livewire:docs-demo-calendar
                view="timeGridWeek"
                initial-date="2026-05-14"
                first-day="0"
                today="2026-05-14"
                time-zone="UTC"
            />
        </div>
    </div>
@endsection
