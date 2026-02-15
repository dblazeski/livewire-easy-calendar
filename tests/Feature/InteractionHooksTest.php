<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('calls onEventClick hook with event id and data', function (): void {
    $component = new class extends LivewireCalendar
    {
        public static ?string $clickedEventId = null;

        public static ?array $clickedEventData = null;

        protected function onEventClick(string $eventId, array $eventData): void
        {
            self::$clickedEventId = $eventId;
            self::$clickedEventData = $eventData;
        }
    };

    Livewire::test($component::class)
        ->call('eventClick', 'evt-1', ['id' => 'evt-1', 'title' => 'Meeting', 'start' => '2026-05-14T09:00:00', 'end' => '2026-05-14T10:00:00']);

    expect($component::$clickedEventId)->toBe('evt-1')
        ->and($component::$clickedEventData['title'])->toBe('Meeting');
});

it('calls onDateSelect hook with start, end, and allDay', function (): void {
    $component = new class extends LivewireCalendar
    {
        public static ?string $selectedStart = null;

        public static ?string $selectedEnd = null;

        public static ?bool $selectedAllDay = null;

        protected function onDateSelect(string $start, string $end, bool $allDay): void
        {
            self::$selectedStart = $start;
            self::$selectedEnd = $end;
            self::$selectedAllDay = $allDay;
        }
    };

    Livewire::test($component::class)
        ->call('dateSelect', '2026-05-14T09:00:00', '2026-05-14T09:30:00', false);

    expect($component::$selectedStart)->toBe('2026-05-14T09:00:00')
        ->and($component::$selectedEnd)->toBe('2026-05-14T09:30:00')
        ->and($component::$selectedAllDay)->toBeFalse();
});

it('calls onEventDrop hook and returns updated events for the visible range', function (): void {
    $component = new class extends LivewireCalendar
    {
        public static ?string $droppedEventId = null;

        public static ?string $droppedNewStart = null;

        public static ?string $droppedNewEnd = null;

        protected function onEventDrop(string $eventId, string $newStart, string $newEnd): void
        {
            self::$droppedEventId = $eventId;
            self::$droppedNewStart = $newStart;
            self::$droppedNewEnd = $newEnd;
        }

        protected function events(DateRange $range): array
        {
            return [
                new CalendarEvent(
                    id: 'evt-1',
                    title: 'Meeting',
                    start: Carbon::parse('2026-05-14T09:00:00Z'),
                    end: Carbon::parse('2026-05-14T10:00:00Z'),
                ),
            ];
        }
    };

    Livewire::test($component::class)
        ->call('eventDrop', 'evt-1', '2026-05-15T10:00:00', '2026-05-15T11:00:00', '2026-05-10', '2026-05-17')
        ->assertReturned(fn (array $returned) => count($returned) === 1 && $returned[0]['id'] === 'evt-1');

    expect($component::$droppedEventId)->toBe('evt-1')
        ->and($component::$droppedNewStart)->toBe('2026-05-15T10:00:00')
        ->and($component::$droppedNewEnd)->toBe('2026-05-15T11:00:00');
});

it('calls onEventResize hook and returns updated events for the visible range', function (): void {
    $component = new class extends LivewireCalendar
    {
        public static ?string $resizedEventId = null;

        public static ?string $resizedNewEnd = null;

        protected function onEventResize(string $eventId, string $newStart, string $newEnd): void
        {
            self::$resizedEventId = $eventId;
            self::$resizedNewEnd = $newEnd;
        }

        protected function events(DateRange $range): array
        {
            return [
                new CalendarEvent(
                    id: 'evt-1',
                    title: 'Meeting',
                    start: Carbon::parse('2026-05-14T09:00:00Z'),
                    end: Carbon::parse('2026-05-14T10:00:00Z'),
                ),
            ];
        }
    };

    Livewire::test($component::class)
        ->call('eventResize', 'evt-1', '2026-05-14T09:00:00', '2026-05-14T11:00:00', '2026-05-10', '2026-05-17')
        ->assertReturned(fn (array $returned) => count($returned) === 1 && $returned[0]['id'] === 'evt-1');

    expect($component::$resizedEventId)->toBe('evt-1')
        ->and($component::$resizedNewEnd)->toBe('2026-05-14T11:00:00');
});
