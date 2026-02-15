@extends('layouts.docs')

@section('title', 'Year View')

@section('content')
    <div class="docs-prose">
        <h1>Year View</h1>

        <p>
            Use <code>multiMonthYear</code> to render a 12-month grid.
        </p>

        <pre><code>&lt;livewire:my-calendar view=&quot;multiMonthYear&quot; initial-date=&quot;2026-05-14&quot; /&gt;</code></pre>

        <h2>Stable Selectors</h2>

        <ul>
            <li><code>[data-testid="multimonth-view"]</code></li>
            <li><code>[data-testid="multimonth-month-YYYY-MM"]</code></li>
        </ul>
    </div>
@endsection
