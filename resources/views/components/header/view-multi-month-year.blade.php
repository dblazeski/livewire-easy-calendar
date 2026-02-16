@php
    $isActive = ($view ?? null) === 'multiMonthYear';
@endphp

<button
    class="lec-view-switcher-btn{{ $isActive ? ' lec-view-switcher-btn--active' : '' }}"
    data-testid="view-btn-multiMonthYear"
    type="button"
    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
    @if (! $isActive)
        wire:click="setView('multiMonthYear')"
    @endif
>
    Year
</button>
