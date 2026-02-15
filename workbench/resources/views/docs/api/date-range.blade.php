@extends('layouts.docs')

@section('title', 'DateRange')

@section('content')
    <div class="docs-prose">
        <h1>DateRange</h1>

        <p>
            <code>Calendar\LivewireCalendar\DateRange</code> holds a start and end <code>Carbon</code> instance.
            The base component uses it when calling your <code>events()</code> and <code>resources()</code> hooks.
        </p>

        <h2>Creation</h2>

        <p>
            The package provides <code>DateRange::fromIso(string $startIso, string $endIso)</code>.
            It uses <code>Carbon::parse()</code> to parse the incoming strings.
        </p>

        <h2>Where it comes from</h2>

        <p>
            In the browser, the calendar requests data by calling Livewire methods like <code>fetchEvents</code>.
            Those methods receive <code>start</code> and <code>end</code> strings (often <code>YYYY-MM-DD</code>), which are converted
            into a <code>DateRange</code>.
        </p>
    </div>
@endsection
