@extends('layouts.docs')

@section('title', 'Drag and Drop')

@section('content')
    <div class="docs-prose">
        <h1>Drag and Drop</h1>

        <p>
            Drag and drop is supported for timed events in time grid views.
            Override <code>onEventDrop(string $eventId, string $newStart, string $newEnd)</code>.
        </p>

        <pre><code>protected function onEventDrop(string $eventId, string $newStart, string $newEnd): void
{
    // Persist changes, then return updated events in events()
}</code></pre>
    </div>
@endsection
