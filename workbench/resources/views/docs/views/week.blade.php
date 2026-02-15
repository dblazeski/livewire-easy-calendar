@extends('layouts.docs')

@section('title', 'Week View')

@section('content')
    <div class="docs-prose">
        <h1>Week View (Time Grid)</h1>

        <p>
            Use <code>timeGridWeek</code> for a 7-day grid with 30-minute slots.
            Timed events render with overlap layout and support interactions.
        </p>

        <pre><code>&lt;livewire:my-calendar view=&quot;timeGridWeek&quot; initial-date=&quot;2026-05-14&quot; /&gt;</code></pre>

        <h2>Stable Selectors</h2>

        <ul>
            <li><code>[data-testid="timegrid"]</code></li>
            <li><code>[data-testid="allday-row"]</code></li>
            <li><code>[data-testid="slot-cell-YYYY-MM-DD-HH:MM"]</code></li>
            <li><code>[data-testid="timed-event-&lt;id&gt;-YYYY-MM-DD"]</code></li>
        </ul>

        <h2>Timed Event Geometry</h2>

        <p>
            Each timed event includes attributes like <code>data-start-min</code> and <code>data-end-min</code> (minutes from midnight)
            and overlap info (<code>data-col</code>, <code>data-col-count</code>).
        </p>
    </div>
@endsection
