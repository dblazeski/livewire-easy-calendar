@extends('layouts.docs')

@section('title', 'Defining Resources')

@section('content')
    <div class="docs-prose">
        <h1>Defining Resources</h1>

        <p>
            Resources are returned by overriding <code>resources(DateRange $range)</code>.
            Each resource must have an <code>id</code> and <code>title</code>.
        </p>

        <pre><code>&lt;?php

use Calendar\LivewireCalendar\CalendarResource;
use Calendar\LivewireCalendar\DateRange;

protected function resources(DateRange $range): array
{
    return [
        new CalendarResource(id: 'room-a', title: 'Conference Room A'),
        new CalendarResource(id: 'room-b', title: 'Conference Room B'),
    ];
}</code></pre>

        <h2>Assign Events to Resources</h2>

        <p>
            Add <code>resourceId</code> to your event payload to attach it to a resource lane.
        </p>
    </div>
@endsection
