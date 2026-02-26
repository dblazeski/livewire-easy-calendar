@extends('layouts.docs')

@section('title', 'Documentation')

@section('content')
    <div class="mb-10 border-b border-docs-border pb-10">
        <h1 class="text-balance font-display text-4xl font-bold lg:text-5xl">
            <span class="block text-docs-text">Livewire</span>
            <span class="block text-docs-accent">Calendar</span>
        </h1>

        <p class="text-pretty mt-4 max-w-2xl text-lg text-docs-muted">
            A powerful, fully-featured calendar component for Laravel.
            Built with Livewire; no third-party calendar UI libraries.
        </p>

        <div class="mt-6 flex flex-wrap gap-3">
            <a
                href="/docs/installation"
                class="inline-flex items-center gap-2 rounded-lg bg-docs-accent px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-docs-accent-hover"
            >
                Get Started
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>

            <a
                href="https://github.com/dblazeski/livewire-easy-calendar"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-2 rounded-lg border border-docs-border bg-white px-5 py-2.5 text-sm font-medium text-docs-text shadow-sm transition-colors duration-150 hover:bg-docs-surface"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
                View on GitHub
            </a>
        </div>
    </div>

    @php
    $features = [
        ['num' => '01', 'title' => 'Multiple Views', 'desc' => 'Month, week, day, list, year, resource timeline, and resource time grid.'],
        ['num' => '02', 'title' => 'Livewire Hooks', 'desc' => 'Fetch events/resources and handle interactions in PHP.'],
        ['num' => '03', 'title' => 'Recurrence', 'desc' => 'RRULE support with exclusion dates (EXDATE).'],
        ['num' => '04', 'title' => 'Interactions', 'desc' => 'Click/select/resize in time grid; drag-and-drop across views.'],
        ['num' => '05', 'title' => 'Resources', 'desc' => 'Assign events to rooms, people, or equipment.'],
        ['num' => '06', 'title' => 'No FullCalendar', 'desc' => 'Custom rendering and interactions; no UI calendar dependency.'],
    ];
    @endphp

    <div class="mb-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($features as $feature)
            <div class="rounded-lg border border-docs-border bg-white p-5">
                <span class="font-mono text-xs font-medium text-docs-accent">{{ $feature['num'] }}</span>
                <div class="mt-2 font-display text-sm font-bold text-docs-text">{{ $feature['title'] }}</div>
                <p class="mt-1.5 text-sm leading-relaxed text-docs-muted">{{ $feature['desc'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="docs-prose">
        <h2>Quick Start</h2>

        <p>Install the package and publish its assets:</p>

        <pre><code>composer require dblazeski/livewire-calendar
php artisan vendor:publish --tag=livewire-calendar-assets</code></pre>

        <p>Create a Livewire component that returns your events:</p>

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
                id: '1',
                title: 'Team Meeting',
                start: Carbon::parse('2026-05-14T09:00:00Z'),
                end: Carbon::parse('2026-05-14T10:00:00Z'),
            ),
        ];
    }
}</code></pre>

        <p>Render it in any Blade view:</p>

        <pre><code>&lt;livewire:my-calendar /&gt;</code></pre>

        <h2>Live Preview</h2>

        <p>
            This preview uses a committed SQLite fixture database from the package workbench.
            Use the view switcher in the toolbar to explore all views, then click, drag, resize, and select time slots.
        </p>

        <div class="not-prose overflow-hidden rounded-lg border border-docs-border bg-white p-4">
            <livewire:docs-demo-calendar
                view="timeGridWeek"
                initial-date="2026-05-14"
                first-day="0"
                today="2026-05-14"
                time-zone="UTC"
            />
        </div>
    </div>
@endsection
