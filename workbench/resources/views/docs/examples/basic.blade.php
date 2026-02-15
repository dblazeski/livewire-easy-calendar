@extends('layouts.docs')

@section('title', 'Basic Calendar Example')

@section('content')
    <div class="docs-prose">
        <h1>Basic Calendar</h1>

        <p>
            This example renders the demo calendar in month view.
            It is backed by the committed SQLite fixture database in <code>workbench/database/demo.sqlite</code>.
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
