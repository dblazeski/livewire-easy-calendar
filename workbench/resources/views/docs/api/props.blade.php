@extends('layouts.docs')

@section('title', 'Component Props')

@section('content')
    <div class="docs-prose">
        <h1>Component Props</h1>

        <p>
            The package base component is <code>Calendar\LivewireCalendar\Livewire\LivewireCalendar</code>.
            It defines a <code>mount()</code> signature you can pass parameters to.
        </p>

        <h2>Mount Signature</h2>

        <pre><code>public function mount(
    ?string $initialDate = null,
    int $firstDay = 0,
    ?string $today = null,
    ?string $view = null,
    ?string $timeZone = null,
): void</code></pre>

        <h2>Defaults</h2>

        <ul>
            <li><code>initialDate</code> defaults to <code>now()-&gt;format('Y-m-d')</code></li>
            <li><code>today</code> defaults to <code>now()-&gt;format('Y-m-d')</code></li>
            <li><code>view</code> defaults to <code>month</code></li>
            <li><code>timeZone</code> defaults to <code>config('app.timezone', 'UTC')</code></li>
        </ul>

        <h2>Overriding mount</h2>

        <p>
            If you override <code>mount()</code> in your component, call <code>parent::mount(...)</code> so the base props are initialized.
        </p>
    </div>
@endsection
