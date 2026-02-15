@extends('layouts.docs')

@section('title', 'Day View')

@section('content')
    <div class="docs-prose">
        <h1>Day View (Time Grid)</h1>

        <p>
            Use <code>timeGridDay</code> for a single-day grid with 30-minute slots.
            This view supports the same interactions as <code>timeGridWeek</code>.
        </p>

        <pre><code>&lt;livewire:my-calendar view=&quot;timeGridDay&quot; initial-date=&quot;2026-05-14&quot; /&gt;</code></pre>
    </div>
@endsection
