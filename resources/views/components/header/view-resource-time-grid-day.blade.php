@php
    $isActive = ($view ?? null) === 'resourceTimeGridDay';
@endphp

<button
    class="lec-view-switcher-btn{{ $isActive ? ' lec-view-switcher-btn--active' : '' }}"
    data-testid="view-btn-resourceTimeGridDay"
    type="button"
    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
    @if (! $isActive)
        wire:click="setView('resourceTimeGridDay')"
    @endif
>
    Resource Day
</button>
