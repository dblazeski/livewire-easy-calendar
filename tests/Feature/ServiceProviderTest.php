<?php

use Calendar\LivewireCalendar\Livewire\LivewireCalendar;

it('registers the livewire-calendar component alias', function (): void {
    expect(app()->bound('livewire.finder'))->toBeTrue();

    expect(app('livewire.finder')->resolveClassComponentClassName('livewire-calendar'))->toBe(LivewireCalendar::class);
});
