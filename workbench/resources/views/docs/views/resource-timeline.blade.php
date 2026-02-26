@extends('layouts.docs')

@section('title', 'Resource Timeline')

@section('content')
    <div class="docs-prose">
        <h1>Resource Timeline (Day)</h1>

        <p>
            Use <code>resourceTimelineDay</code> to render a single-day timeline grouped by resources.
            This view uses your <code>resources()</code> and <code>events()</code> methods to build the timeline.
        </p>

        <pre><code>&lt;livewire:my-calendar view=&quot;resourceTimelineDay&quot; initial-date=&quot;2026-05-14&quot; /&gt;</code></pre>

        <h2>Stable Selectors</h2>

        <ul>
            <li><code>[data-testid="resource-timeline"]</code></li>
            <li><code>[data-testid="resource-row-&lt;resourceId&gt;"]</code></li>
            <li><code>[data-testid="resource-label-&lt;resourceId&gt;"]</code></li>
            <li><code>[data-testid="resource-event-&lt;eventId&gt;-&lt;resourceId&gt;"]</code></li>
        </ul>
    </div>
@endsection
