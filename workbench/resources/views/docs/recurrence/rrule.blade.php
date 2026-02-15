@extends('layouts.docs')

@section('title', 'RRULE Basics')

@section('content')
    <div class="docs-prose">
        <h1>RRULE Basics</h1>

        <p>
            Recurrence is driven by the <code>rrule</code> field on an event.
            The package expands occurrences in the browser using the <code>rrule</code> library.
        </p>

        <h2>Event Payload</h2>

        <pre><code>[
  'id' =&gt; 'daily-standup',
  'title' =&gt; 'Daily Standup',
  'start' =&gt; '2026-05-11T09:00:00+00:00',
  'end' =&gt; '2026-05-11T09:15:00+00:00',
  'rrule' =&gt; 'FREQ=DAILY;COUNT=5',
]</code></pre>

        <h2>Occurrence IDs</h2>

        <p>
            Each expanded occurrence receives a derived <code>id</code> and includes a <code>recurrenceId</code> pointing to the base event.
        </p>
    </div>
@endsection
