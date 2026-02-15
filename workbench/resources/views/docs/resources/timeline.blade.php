@extends('layouts.docs')

@section('title', 'Resource Timeline')

@section('content')
    <div class="docs-prose">
        <h1>Resource Timeline</h1>

        <p>
            The <code>resourceTimelineDay</code> view groups events by resources.
            It loads resources and events for a single day.
        </p>

        <div class="not-prose overflow-hidden rounded-lg border border-docs-border bg-white p-4">
            <livewire:docs-demo-calendar
                view="resourceTimelineDay"
                initial-date="2026-05-14"
                first-day="0"
                today="2026-05-14"
                time-zone="UTC"
            />
        </div>
    </div>
@endsection
