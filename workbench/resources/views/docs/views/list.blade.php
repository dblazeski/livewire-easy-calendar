@extends('layouts.docs')

@section('title', 'List View')

@section('content')
    <div class="docs-prose">
        <h1>List View</h1>

        <p>
            Use <code>listWeek</code> to render a grouped list of events for the visible week.
        </p>

        <pre><code>&lt;livewire:my-calendar view=&quot;listWeek&quot; initial-date=&quot;2026-05-14&quot; /&gt;</code></pre>

        <h2>Stable Selectors</h2>

        <ul>
            <li><code>[data-testid="list-view"]</code></li>
            <li><code>[data-testid="list-day-YYYY-MM-DD"]</code></li>
            <li><code>[data-testid="list-event-&lt;id&gt;"]</code></li>
        </ul>
    </div>
@endsection
