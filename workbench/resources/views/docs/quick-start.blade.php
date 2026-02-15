@extends('layouts.docs')

@section('title', 'Quick Start')

@section('content')
    <div class="docs-prose">
        <h1>Quick Start</h1>

        <p>
            Create a Livewire component that extends the package base class and implement <code>events()</code>.
        </p>

        <pre><code>&lt;?php

namespace App\Livewire;

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;

class MyCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'evt-1',
                title: 'Team Meeting',
                start: Carbon::parse('2026-05-14T09:00:00Z'),
                end: Carbon::parse('2026-05-14T10:00:00Z'),
            ),
        ];
    }
}</code></pre>

        <p>Render your component in Blade:</p>

        <pre><code>&lt;livewire:my-calendar /&gt;</code></pre>

        <h2>Choose a View</h2>

        <p>
            Use the <code>view</code> prop to render a different view.
            Supported values are documented under <a href="/docs/views/month">Views</a>.
        </p>

        <pre><code>&lt;livewire:my-calendar view=&quot;timeGridWeek&quot; /&gt;</code></pre>

        <h2>Optional: Resources</h2>

        <p>
            If you want to use resource timeline views, implement <code>resources()</code> and add <code>resourceId</code> to your events.
        </p>
    </div>
@endsection
