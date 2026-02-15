<div>
    <link rel="stylesheet" href="{{ asset('vendor/livewire-calendar/livewire-calendar.css') }}">

    <div
        wire:ignore
        data-livewire-calendar-root
        data-livewire-calendar-initial-date="{{ $initialDate }}"
        data-livewire-calendar-first-day="{{ $firstDay }}"
        data-livewire-calendar-today="{{ $today }}"
        data-livewire-calendar-view="{{ $view }}"
        data-livewire-calendar-time-zone="{{ $timeZone }}"
    ></div>

    <script src="{{ asset('vendor/livewire-calendar/livewire-calendar.js') }}"></script>
</div>
