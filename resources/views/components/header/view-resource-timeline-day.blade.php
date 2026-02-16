@php
    $isActive = ($view ?? null) === 'resourceTimelineDay';
@endphp

<button
    class="lec-view-switcher-btn{{ $isActive ? ' lec-view-switcher-btn--active' : '' }}"
    data-testid="view-btn-resourceTimelineDay"
    type="button"
    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
    @if (! $isActive)
        wire:click="setView('resourceTimelineDay')"
    @endif
>
    Timeline
</button>
