<?php

use Calendar\LivewireCalendar\CalendarResource;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Livewire\Livewire;

it('returns an empty array by default', function (): void {
    Livewire::test(LivewireCalendar::class)
        ->call('fetchResources', '2026-01-01T00:00:00Z', '2026-01-31T23:59:59Z')
        ->assertReturned([]);
});

it('allows consumers to provide resources as arrays via the resources hook', function (): void {
    $component = new class extends LivewireCalendar
    {
        protected function resources(DateRange $range): array
        {
            return [
                [
                    'id' => 'res-1',
                    'title' => 'Conference Room A',
                ],
            ];
        }
    };

    Livewire::test($component::class)
        ->call('fetchResources', '2026-02-01T00:00:00Z', '2026-02-28T23:59:59Z')
        ->assertReturned(fn (array $returned) => $returned[0]['id'] === 'res-1'
            && $returned[0]['title'] === 'Conference Room A'
            && count($returned) === 1
        );
});

it('normalizes CalendarResource objects to arrays', function (): void {
    $component = new class extends LivewireCalendar
    {
        protected function resources(DateRange $range): array
        {
            return [
                new CalendarResource(
                    id: 'res-2',
                    title: 'Meeting Room B',
                ),
            ];
        }
    };

    Livewire::test($component::class)
        ->call('fetchResources', '2026-03-01T09:00:00Z', '2026-03-01T17:00:00Z')
        ->assertReturned(fn (array $returned) => $returned[0]['id'] === 'res-2'
            && $returned[0]['title'] === 'Meeting Room B'
        );
});

it('passes the correct date range to the resources hook', function (): void {
    $component = new class extends LivewireCalendar
    {
        public static ?DateRange $capturedRange = null;

        protected function resources(DateRange $range): array
        {
            self::$capturedRange = $range;

            return [];
        }
    };

    Livewire::test($component::class)
        ->call('fetchResources', '2026-06-01T00:00:00Z', '2026-06-30T23:59:59Z');

    expect($component::$capturedRange)
        ->not->toBeNull()
        ->and($component::$capturedRange->start->toIso8601String())->toBe('2026-06-01T00:00:00+00:00')
        ->and($component::$capturedRange->end->toIso8601String())->toBe('2026-06-30T23:59:59+00:00');
});

it('preserves extra fields from CalendarResource', function (): void {
    $component = new class extends LivewireCalendar
    {
        protected function resources(DateRange $range): array
        {
            return [
                new CalendarResource(
                    id: 'res-3',
                    title: 'Projector',
                    extra: ['capacity' => 50, 'location' => 'Building A'],
                ),
            ];
        }
    };

    Livewire::test($component::class)
        ->call('fetchResources', '2026-04-01T00:00:00Z', '2026-04-30T23:59:59Z')
        ->assertReturned(fn (array $returned) => $returned[0]['capacity'] === 50
            && $returned[0]['location'] === 'Building A'
        );
});

it('handles mixed arrays and CalendarResource objects', function (): void {
    $component = new class extends LivewireCalendar
    {
        protected function resources(DateRange $range): array
        {
            return [
                ['id' => 'res-4', 'title' => 'Room A'],
                new CalendarResource(id: 'res-5', title: 'Room B'),
                ['id' => 'res-6', 'title' => 'Room C'],
            ];
        }
    };

    Livewire::test($component::class)
        ->call('fetchResources', '2026-05-01T00:00:00Z', '2026-05-31T23:59:59Z')
        ->assertReturned(fn (array $returned) => count($returned) === 3
            && $returned[0]['id'] === 'res-4'
            && $returned[1]['id'] === 'res-5'
            && $returned[2]['id'] === 'res-6'
        );
});
