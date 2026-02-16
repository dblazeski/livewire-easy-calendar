@php
    $isActive = ($view ?? null) === 'timeGridWeek';
@endphp

<button
    class="lec-view-switcher-btn{{ $isActive ? ' lec-view-switcher-btn--active' : '' }}"
    data-testid="view-btn-timeGridWeek"
    type="button"
    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
    @if (! $isActive)
        wire:click="setView('timeGridWeek')"
    @endif
>
    Week
</button>
