@php
    $isActive = ($view ?? null) === 'month';
@endphp

<button
    class="lec-view-switcher-btn{{ $isActive ? ' lec-view-switcher-btn--active' : '' }}"
    data-testid="view-btn-month"
    type="button"
    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
    @if (! $isActive)
        wire:click="setView('month')"
    @endif
>
    Month
</button>
