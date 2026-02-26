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
            <tr>
                <td><code>:views</code></td>
                <td><code>array</code></td>
                <td><code>[]</code></td>
                <td>Per-view blade path overrides.</td>
            </tr>
            <tr>
                <td><code>:components</code></td>
                <td><code>array</code></td>
                <td><code>[]</code></td>
                <td>Per-component blade path overrides.</td>
            </tr>
            <tr>
                <td><code>:event-time-management-enabled</code></td>
                <td><code>bool</code></td>
                <td><code>true</code></td>
                <td>Set to <code>false</code> to disable drag-and-drop and resize.</td>
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
    :views=&quot;['month' =&gt; 'my-views.custom-month']&quot;
    :components=&quot;['header' =&gt; 'my-views.custom-header']&quot;
    :event-time-management-enabled=&quot;false&quot;
/&gt;</code></pre>

        <blockquote>
            <p>
                In this package, date strings passed to your PHP hooks are typically ISO-like strings.
                For example, selections and drag interactions use <code>YYYY-MM-DDTHH:mm:ss</code> (no offset).
            </p>
        </blockquote>

        <h2>Config File</h2>

        <p>
            Publish the config file to customise global defaults:
        </p>

        <pre><code>php artisan vendor:publish --tag=livewire-calendar</code></pre>

        <p>
            The config file (<code>config/livewire-calendar.php</code>) supports:
        </p>

        <ul>
            <li><code>views</code> &mdash; global view overrides (same as the mount parameter, applied to all instances)</li>
            <li><code>components</code> &mdash; global component overrides (header, nav buttons, view switchers)</li>
            <li><code>styles</code> &mdash; CSS custom property values for theming (font, event colors, selection, drag ghost, etc.)</li>
        </ul>

        <p>
            Mount parameters take precedence over config-file values. Config values take precedence over built-in defaults.
        </p>
    </div>
@endsection
