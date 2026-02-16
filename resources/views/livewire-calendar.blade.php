<div>
    <link rel="stylesheet" href="{{ asset('vendor/livewire-calendar/livewire-calendar.css') }}">

    @php
        $viewData = $this->viewData;
        $calendarView = $this->calendarView;
    @endphp

    <div
        data-livewire-calendar-root
        data-livewire-calendar-initial-date="{{ $initialDate }}"
        data-livewire-calendar-first-day="{{ $firstDay }}"
        data-livewire-calendar-today="{{ $today }}"
        data-livewire-calendar-view="{{ $view }}"
        data-livewire-calendar-time-zone="{{ $timeZone }}"
        data-livewire-calendar-event-time-management-enabled="{{ $eventTimeManagementEnabled ? 'true' : 'false' }}"
        data-livewire-calendar-renderer="blade"
        style="
            --lec-font-family: {{ config('livewire-calendar.styles.font_family', 'system-ui, -apple-system, sans-serif') }};
            --lec-month-event-bg: {{ config('livewire-calendar.styles.month_event_bg', '#3b82f6') }};
            --lec-month-event-text: {{ config('livewire-calendar.styles.month_event_text', '#ffffff') }};
            --lec-timed-event-bg: {{ config('livewire-calendar.styles.timed_event_bg', '#3b82f6') }};
            --lec-timed-event-text: {{ config('livewire-calendar.styles.timed_event_text', '#ffffff') }};
            --lec-allday-event-bg: {{ config('livewire-calendar.styles.allday_event_bg', '#3b82f6') }};
            --lec-allday-event-text: {{ config('livewire-calendar.styles.allday_event_text', '#ffffff') }};
            --lec-resource-event-bg: {{ config('livewire-calendar.styles.resource_event_bg', '#3b82f6') }};
            --lec-resource-event-text: {{ config('livewire-calendar.styles.resource_event_text', '#ffffff') }};
            --lec-drag-ghost-bg: {{ config('livewire-calendar.styles.drag_ghost_bg', '#3b82f6') }};
            --lec-drag-ghost-text: {{ config('livewire-calendar.styles.drag_ghost_text', '#ffffff') }};
            --lec-selected-outline: {{ config('livewire-calendar.styles.selected_outline', '#1d4ed8') }};
            --lec-selected-shadow: {{ config('livewire-calendar.styles.selected_shadow', 'rgba(29, 78, 216, 0.3)') }};
            --lec-drop-target-bg: {{ config('livewire-calendar.styles.drop_target_bg', 'rgba(59, 130, 246, 0.1)') }};
            --lec-drop-target-outline: {{ config('livewire-calendar.styles.drop_target_outline', 'rgba(59, 130, 246, 0.4)') }};
            --lec-selection-bg: {{ config('livewire-calendar.styles.selection_bg', 'rgba(59, 130, 246, 0.2)') }};
            --lec-selection-outline: {{ config('livewire-calendar.styles.selection_outline', 'rgba(59, 130, 246, 0.5)') }};
        "
    >
        @include($this->componentView('header'), ['view' => $view])

        <div class="lec-title" data-testid="calendar-title">
            {{ $viewData['title'] ?? '' }}
        </div>

        @include($calendarView, $viewData)
    </div>

    <script src="{{ asset('vendor/livewire-calendar/livewire-calendar.js') }}"></script>
</div>
