<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('returns an empty array by default', function (): void {
    Livewire::test(LivewireCalendar::class)
        ->call('fetchEvents', '2026-01-01T00:00:00Z', '2026-01-31T23:59:59Z')
        ->assertReturned([]);
});

it('allows consumers to provide events as arrays via the events hook', function (): void {
    $component = new class extends LivewireCalendar
    {
        protected function events(DateRange $range): array
        {
            return [
                [
                    'id' => 'evt-1',
                    'title' => 'Team Meeting',
                    'start' => $range->start->toIso8601String(),
                    'end' => $range->end->toIso8601String(),
                ],
            ];
        }
    };

    Livewire::test($component::class)
        ->call('fetchEvents', '2026-02-01T00:00:00Z', '2026-02-28T23:59:59Z')
        ->assertReturned(fn (array $returned) => $returned[0]['id'] === 'evt-1'
            && $returned[0]['title'] === 'Team Meeting'
            && count($returned) === 1
        );
});

it('normalizes CalendarEvent objects to arrays', function (): void {
    $component = new class extends LivewireCalendar
    {
        protected function events(DateRange $range): array
        {
            return [
                new CalendarEvent(
                    id: 'evt-2',
                    title: 'Standup',
                    start: $range->start,
                    end: $range->start->copy()->addHour(),
                ),
            ];
        }
    };

    Livewire::test($component::class)
        ->call('fetchEvents', '2026-03-01T09:00:00Z', '2026-03-01T17:00:00Z')
        ->assertReturned(fn (array $returned) => $returned[0]['id'] === 'evt-2'
            && $returned[0]['title'] === 'Standup'
            && is_string($returned[0]['start'])
            && is_string($returned[0]['end'])
        );
});

it('passes the correct date range to the events hook', function (): void {
    $capturedRange = null;

    $component = new class extends LivewireCalendar
    {
        public static ?DateRange $capturedRange = null;

        protected function events(DateRange $range): array
        {
            self::$capturedRange = $range;

            return [];
        }
    };

    Livewire::test($component::class)
        ->call('fetchEvents', '2026-06-01T00:00:00Z', '2026-06-30T23:59:59Z');

    expect($component::$capturedRange)
        ->not->toBeNull()
        ->and($component::$capturedRange->start->toIso8601String())->toBe('2026-06-01T00:00:00+00:00')
        ->and($component::$capturedRange->end->toIso8601String())->toBe('2026-06-30T23:59:59+00:00');
});

it('preserves extra fields from CalendarEvent', function (): void {
    $component = new class extends LivewireCalendar
    {
        protected function events(DateRange $range): array
        {
            return [
                new CalendarEvent(
                    id: 'evt-3',
                    title: 'Workshop',
                    start: Carbon::parse('2026-04-10T10:00:00Z'),
                    end: Carbon::parse('2026-04-10T12:00:00Z'),
                    extra: ['color' => '#ff0000', 'location' => 'Room A'],
                ),
            ];
        }
    };

    Livewire::test($component::class)
        ->call('fetchEvents', '2026-04-01T00:00:00Z', '2026-04-30T23:59:59Z')
        ->assertReturned(fn (array $returned) => $returned[0]['color'] === '#ff0000'
            && $returned[0]['location'] === 'Room A'
        );
});
