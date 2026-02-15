@extends('layouts.docs')

@section('title', 'Resource Scheduling Example')

@section('content')
    <div class="docs-prose">
        <h1>Resource Scheduling</h1>

        <p>
            This example uses <code>resourceTimelineDay</code> and shows events assigned to resources.
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
