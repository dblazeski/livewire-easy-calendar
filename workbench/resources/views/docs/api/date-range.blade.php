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
            During rendering, the component computes the visible date range and passes it as a
            <code>DateRange</code> to your <code>events()</code> and <code>resources()</code> hooks.
        </p>
    </div>
@endsection
