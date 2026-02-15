@extends('layouts.docs')

@section('title', 'CalendarResource')

@section('content')
    <div class="docs-prose">
        <h1>CalendarResource</h1>

        <p>
            <code>Calendar\LivewireCalendar\CalendarResource</code> is a helper value object for resource data.
            You can return either arrays or <code>CalendarResource</code> objects from <code>resources()</code>.
        </p>

        <h2>Constructor</h2>

        <pre><code>new CalendarResource(
    id: string,
    title: string,
    extra: array = [],
)</code></pre>

        <h2>Example</h2>

        <pre><code>&lt;?php

use Calendar\LivewireCalendar\CalendarResource;

return [
    new CalendarResource(
        id: 'room-a',
        title: 'Conference Room A',
        extra: ['capacity' => 10],
    ),
];</code></pre>

        <blockquote>
            <p>
                To show events in a resource timeline view, your event payload must include <code>resourceId</code>.
            </p>
        </blockquote>
    </div>
@endsection
