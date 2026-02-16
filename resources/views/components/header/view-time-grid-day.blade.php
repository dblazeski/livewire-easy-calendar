@php
    $isActive = ($view ?? null) === 'timeGridDay';
@endphp

<button
    class="lec-view-switcher-btn{{ $isActive ? ' lec-view-switcher-btn--active' : '' }}"
    data-testid="view-btn-timeGridDay"
    type="button"
    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
    @if (! $isActive)
        wire:click="setView('timeGridDay')"
    @endif
>
    Day
</button>
