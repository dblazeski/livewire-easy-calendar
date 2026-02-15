@extends('layouts.docs')

@section('title', 'Date Select')

@section('content')
    <div class="docs-prose">
        <h1>Date Select</h1>

        <p>
            Override <code>onDateSelect(string $start, string $end, bool $allDay)</code> to handle selections.
            Selections are created by clicking time slots in time grid views.
        </p>

        <pre><code>protected function onDateSelect(string $start, string $end, bool $allDay): void
{
    // start/end are formatted as YYYY-MM-DDTHH:mm:ss (no offset)
}</code></pre>
    </div>
@endsection
