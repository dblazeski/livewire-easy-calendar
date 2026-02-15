@extends('layouts.docs')

@section('title', 'Exclusion Dates')

@section('content')
    <div class="docs-prose">
        <h1>Exclusion Dates (EXDATE)</h1>

        <p>
            Use <code>exdate</code> to exclude specific occurrences from a recurring event.
            This is an array of strings.
        </p>

        <pre><code>[
  'id' =&gt; 'daily-standup',
  'title' =&gt; 'Daily Standup',
  'start' =&gt; '2026-05-11T09:00:00+00:00',
  'end' =&gt; '2026-05-11T09:15:00+00:00',
  'rrule' =&gt; 'FREQ=DAILY;COUNT=5',
  'exdate' =&gt; ['2026-05-13'],
]</code></pre>

        <blockquote>
            <p>
                If an <code>exdate</code> entry is date-only (YYYY-MM-DD), the package applies the event start time in the calendar time zone.
            </p>
        </blockquote>
    </div>
@endsection
