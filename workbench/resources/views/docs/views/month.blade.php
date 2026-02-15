@extends('layouts.docs')

@section('title', 'Month View')

@section('content')
    <div class="docs-prose">
        <h1>Month View</h1>

        <p>
            The default view is <code>month</code>. It renders a 6-week grid (42 day cells) with a toolbar.
        </p>

        <pre><code>&lt;livewire:my-calendar view=&quot;month&quot; initial-date=&quot;2026-05-01&quot; /&gt;</code></pre>

        <h2>Stable Selectors</h2>

        <ul>
            <li><code>[data-testid="calendar-toolbar"]</code></li>
            <li><code>[data-testid="btn-prev"]</code>, <code>[data-testid="btn-today"]</code>, <code>[data-testid="btn-next"]</code></li>
            <li><code>[data-testid="month-grid"]</code></li>
            <li><code>[data-testid="day-cell-YYYY-MM-DD"]</code></li>
        </ul>

        <h2>Events</h2>

        <p>
            Events are appended to the day cell that matches the event's <code>start</code> date.
            (Multi-day rendering is not expanded in the month grid.)
        </p>
    </div>
@endsection
