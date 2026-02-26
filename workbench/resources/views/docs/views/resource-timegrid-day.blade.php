@extends('layouts.docs')

@section('title', 'Resource Time Grid')

@section('content')
    <div class="docs-prose">
        <h1>Resource Time Grid (Day)</h1>

        <p>
            Use <code>resourceTimeGridDay</code> to render a single-day vertical time grid with one column per resource.
            This view uses your <code>resources()</code> and <code>events()</code> methods to build the grid.
        </p>

        <pre><code>&lt;livewire:my-calendar view=&quot;resourceTimeGridDay&quot; initial-date=&quot;2026-05-14&quot; /&gt;</code></pre>

        <h2>Stable Selectors</h2>

        <ul>
            <li><code>[data-testid="resource-timegrid"]</code></li>
            <li><code>[data-testid="resource-timegrid-header-&lt;resourceId&gt;"]</code></li>
            <li><code>[data-testid="resource-timegrid-allday-row"]</code></li>
            <li><code>[data-testid="resource-timegrid-allday-cell-&lt;resourceId&gt;"]</code></li>
            <li><code>[data-testid="resource-allday-event-&lt;eventId&gt;-&lt;resourceId&gt;-&lt;date&gt;"]</code></li>
            <li><code>[data-testid="resource-slot-cell-&lt;resourceId&gt;-&lt;date&gt;-&lt;time&gt;"]</code></li>
            <li><code>[data-testid="resource-timegrid-body-&lt;resourceId&gt;"]</code></li>
            <li><code>[data-testid="resource-timed-event-&lt;eventId&gt;-&lt;resourceId&gt;-&lt;date&gt;"]</code></li>
            <li><code>[data-testid="resource-timed-event-resize-handle-&lt;eventId&gt;-&lt;resourceId&gt;-&lt;date&gt;"]</code></li>
        </ul>
    </div>
@endsection
