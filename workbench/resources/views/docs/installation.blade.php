@extends('layouts.docs')

@section('title', 'Installation')

@section('content')
    <div class="docs-prose">
        <h1>Installation</h1>

        <p>Install the package with Composer:</p>

        <pre><code>composer require dblazeski/livewire-calendar</code></pre>

        <p>Publish the package assets (CSS + JavaScript):</p>

        <pre><code>php artisan vendor:publish --tag=livewire-calendar-assets</code></pre>

        <blockquote>
            <p>
                The rendered calendar view includes <code>&lt;link&gt;</code> and <code>&lt;script&gt;</code> tags that reference files under
                <code>public/vendor/livewire-calendar</code>. Publishing assets is required.
            </p>
        </blockquote>

        <h2>Livewire Requirements</h2>

        <p>
            In your application layout, make sure you include Livewire's styles and scripts:
        </p>

        <pre><code>&lt;!doctype html&gt;
&lt;html&gt;
&lt;head&gt;
    @@livewireStyles
&lt;/head&gt;
&lt;body&gt;
    @@livewireScripts
&lt;/body&gt;
&lt;/html&gt;</code></pre>

        <h2>Verify</h2>

        <p>Render the package component once to confirm the assets are loading:</p>

        <pre><code>&lt;livewire:livewire-calendar /&gt;</code></pre>
    </div>
@endsection
