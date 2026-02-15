@extends('layouts.docs')

@section('title', 'Event Resize')

@section('content')
    <div class="docs-prose">
        <h1>Event Resize</h1>

        <p>
            Resize is supported for timed events in time grid views using the resize handle.
            Override <code>onEventResize(string $eventId, string $newStart, string $newEnd)</code>.
        </p>

        <pre><code>protected function onEventResize(string $eventId, string $newStart, string $newEnd): void
{
    // Persist changes, then return updated events in events()
}</code></pre>
    </div>
@endsection
