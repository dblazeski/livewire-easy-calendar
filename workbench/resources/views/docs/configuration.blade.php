@extends('layouts.docs')

@section('title', 'Configuration')

@section('content')
    <div class="docs-prose">
        <h1>Configuration</h1>

        <p>
            The calendar component is configured using mount parameters (passed as Blade attributes).
            These control navigation, view selection, and how dates are interpreted in the browser.
        </p>

        <table>
            <thead>
            <tr>
                <th>Prop</th>
                <th>Type</th>
                <th>Default</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><code>initial-date</code></td>
                <td><code>string</code></td>
                <td><code>today</code></td>
                <td>Anchor date used by the initial view.</td>
            </tr>
            <tr>
                <td><code>today</code></td>
                <td><code>string</code></td>
                <td><code>now()</code></td>
                <td>Used by the toolbar &quot;Today&quot; button; set it in tests for determinism.</td>
            </tr>
            <tr>
                <td><code>first-day</code></td>
                <td><code>int</code></td>
                <td><code>0</code></td>
                <td>0=Sunday, 1=Monday, ...</td>
            </tr>
            <tr>
                <td><code>view</code></td>
                <td><code>string</code></td>
                <td><code>month</code></td>
                <td>One of the supported view names.</td>
            </tr>
            <tr>
                <td><code>time-zone</code></td>
                <td><code>string</code></td>
                <td><code>config('app.timezone')</code></td>
                <td>IANA time zone string used in the browser (Luxon).</td>
            </tr>
            </tbody>
        </table>

        <h2>Example</h2>

        <pre><code>&lt;livewire:my-calendar
    view=&quot;timeGridWeek&quot;
    initial-date=&quot;2026-05-14&quot;
    today=&quot;2026-05-14&quot;
    first-day=&quot;0&quot;
    time-zone=&quot;UTC&quot;
/&gt;</code></pre>

        <blockquote>
            <p>
                In this package, date strings passed to your PHP hooks are typically ISO-like strings.
                For example, selections and drag interactions use <code>YYYY-MM-DDTHH:mm:ss</code> (no offset).
            </p>
        </blockquote>
    </div>
@endsection
