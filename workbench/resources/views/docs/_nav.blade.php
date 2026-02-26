@php
$sections = [
    'Getting Started' => [
        ['label' => 'Installation', 'href' => '/docs/installation'],
        ['label' => 'Quick Start', 'href' => '/docs/quick-start'],
        ['label' => 'Configuration', 'href' => '/docs/configuration'],
    ],
    'API' => [
        ['label' => 'CalendarEvent', 'href' => '/docs/api/calendar-event'],
        ['label' => 'CalendarResource', 'href' => '/docs/api/calendar-resource'],
        ['label' => 'DateRange', 'href' => '/docs/api/date-range'],
        ['label' => 'Component Props', 'href' => '/docs/api/props'],
    ],
    'Views' => [
        ['label' => 'Month', 'href' => '/docs/views/month'],
        ['label' => 'Week', 'href' => '/docs/views/week'],
        ['label' => 'Day', 'href' => '/docs/views/day'],
        ['label' => 'List', 'href' => '/docs/views/list'],
        ['label' => 'Year', 'href' => '/docs/views/year'],
        ['label' => 'Resource Timeline', 'href' => '/docs/views/resource-timeline'],
        ['label' => 'Resource Time Grid', 'href' => '/docs/views/resource-timegrid-day'],
    ],
    'Recurrence' => [
        ['label' => 'RRULE Basics', 'href' => '/docs/recurrence/rrule'],
        ['label' => 'Exclusion Dates', 'href' => '/docs/recurrence/exclusions'],
        ['label' => 'DST + Time Zones', 'href' => '/docs/recurrence/dst'],
    ],
    'Resources' => [
        ['label' => 'Defining Resources', 'href' => '/docs/resources/defining'],
        ['label' => 'Resource Timeline', 'href' => '/docs/resources/timeline'],
    ],
    'Interactions' => [
        ['label' => 'Event Click', 'href' => '/docs/interactions/event-click'],
        ['label' => 'Date Select', 'href' => '/docs/interactions/date-select'],
        ['label' => 'Drag and Drop', 'href' => '/docs/interactions/drag-drop'],
        ['label' => 'Event Resize', 'href' => '/docs/interactions/event-resize'],
    ],
    'Examples' => [
        ['label' => 'Basic Calendar', 'href' => '/docs/examples/basic'],
        ['label' => 'Recurring Events', 'href' => '/docs/examples/recurring'],
        ['label' => 'Resource Scheduling', 'href' => '/docs/examples/resources'],
    ],
];

$isActive = fn (string $href): bool => request()->is(ltrim($href, '/'));
@endphp

<div class="flex h-full flex-col">
    <div class="flex items-center gap-3 px-6 pb-4 pt-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-7 shrink-0 text-docs-nav-active" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <path d="M3 10h18" />
            <path d="M8 2v4M16 2v4" />
        </svg>
        <div>
            <a href="/docs" class="font-display text-sm font-bold text-white transition-colors duration-150 hover:text-docs-nav-active">
                Livewire Calendar
            </a>
            <div class="font-mono text-[0.625rem] text-docs-nav-muted">v0.x</div>
        </div>
    </div>

    <div class="mx-6 border-t border-white/10"></div>

    <nav class="docs-sidebar-scroll flex-1 overflow-y-auto px-4 py-4" aria-label="Documentation">
        @foreach ($sections as $heading => $items)
            <div class="{{ $loop->first ? '' : 'mt-6' }}">
                <h4 class="mb-1 px-2 text-[0.6875rem] font-semibold uppercase tracking-wider text-docs-nav-muted">
                    {{ $heading }}
                </h4>

                <ul>
                    @foreach ($items as $item)
                        <li>
                            <a
                                href="{{ $item['href'] }}"
                                @class([
                                    'block rounded-md px-2 py-1.5 text-[0.8125rem] transition-colors duration-150',
                                    'font-medium text-docs-nav-active bg-white/[0.06]' => $isActive($item['href']),
                                    'text-docs-nav hover:text-white hover:bg-white/[0.04]' => ! $isActive($item['href']),
                                ])
                            >
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-white/10 px-6 py-4">
        <a
            href="https://github.com/dblazeski/livewire-easy-calendar"
            target="_blank"
            rel="noopener"
            class="flex items-center gap-2 text-xs text-docs-nav-muted transition-colors duration-150 hover:text-docs-nav"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
            </svg>
            <span>GitHub</span>
            <span class="ml-auto rounded bg-white/10 px-1.5 py-0.5 font-mono text-[0.625rem]">MIT</span>
        </a>
    </div>
</div>
