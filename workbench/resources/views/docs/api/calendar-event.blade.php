@extends('layouts.docs')

@section('title', 'CalendarEvent')

@section('content')
    <div class="docs-prose">
        <h1>CalendarEvent</h1>

        <p>
            <code>Calendar\LivewireCalendar\CalendarEvent</code> is a helper value object that normalizes your event data.
            You can return either arrays or <code>CalendarEvent</code> objects from <code>events()</code>.
        </p>

        <h2>Constructor</h2>

        <pre><code>new CalendarEvent(
    id: string,
    title: string,
    start: Carbon,
    end: Carbon,
    extra: array = [],
)</code></pre>

        <h2>Serialization</h2>

        <p>
            The component will convert <code>CalendarEvent</code> objects using <code>toArray()</code>.
            It produces an array with <code>id</code>, <code>title</code>, <code>start</code>, <code>end</code>, and spreads <code>extra</code> into the top level.
        </p>

        <h2>Example</h2>

        <pre><code>&lt;?php

use Calendar\LivewireCalendar\CalendarEvent;
use Illuminate\Support\Carbon;

return [
    new CalendarEvent(
        id: 'evt-123',
        title: 'Workshop',
        start: Carbon::parse('2026-05-14T14:00:00Z'),
        end: Carbon::parse('2026-05-14T16:00:00Z'),
        extra: [
            'allDay' => false,
            'rrule' => 'FREQ=DAILY;COUNT=3',
            'exdate' => ['2026-05-15'],
            'resourceId' => 'room-a',
        ],
    ),
];</code></pre>
    </div>
@endsection
