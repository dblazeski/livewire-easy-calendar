<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('preserves rrule and exdate extra fields from CalendarEvent', function (): void {
    $component = new class extends LivewireCalendar
    {
        protected function events(DateRange $range): array
        {
            return [
                new CalendarEvent(
                    id: 'rec-evt-1',
                    title: 'Recurring Meeting',
                    start: Carbon::parse('2026-05-14T10:00:00Z'),
                    end: Carbon::parse('2026-05-14T11:00:00Z'),
                    extra: [
                        'rrule' => 'FREQ=DAILY;COUNT=3',
                        'exdate' => ['2026-05-15'],
                    ],
                ),
            ];
        }
    };

    Livewire::test($component::class)
        ->call('fetchEvents', '2026-05-01', '2026-06-01')
        ->assertReturned(fn (array $returned) => $returned[0]['id'] === 'rec-evt-1'
            && $returned[0]['rrule'] === 'FREQ=DAILY;COUNT=3'
            && $returned[0]['exdate'] === ['2026-05-15']
        );
});

it('allows setting the timeZone mount prop', function (): void {
    $calendar = new LivewireCalendar;
    $calendar->mount(timeZone: 'America/New_York');

    expect($calendar->timeZone)->toBe('America/New_York');
});
