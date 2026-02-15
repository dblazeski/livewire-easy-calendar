<?php

namespace Calendar\LivewireCalendar\Livewire;

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\CalendarResource;
use Calendar\LivewireCalendar\DateRange;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LivewireCalendar extends Component
{
    public string $initialDate;

    public int $firstDay = 0;

    public string $today;

    public string $view = 'month';

    public string $timeZone;

    public function mount(?string $initialDate = null, int $firstDay = 0, ?string $today = null, ?string $view = null, ?string $timeZone = null): void
    {
        $this->initialDate = $initialDate ?? now()->format('Y-m-d');
        $this->firstDay = $firstDay;
        $this->today = $today ?? now()->format('Y-m-d');
        $this->view = $view ?? 'month';

        $this->timeZone = $timeZone ?? config('app.timezone', 'UTC');
    }

    public function fetchEvents(string $startIso, string $endIso): array
    {
        $range = DateRange::fromIso($startIso, $endIso);

        return array_map(
            fn (array|CalendarEvent $event) => $event instanceof CalendarEvent
                ? $event->toArray()
                : $event,
            $this->events($range),
        );
    }

    protected function events(DateRange $_range): array
    {
        return [];
    }

    public function fetchResources(string $startIso, string $endIso): array
    {
        $range = DateRange::fromIso($startIso, $endIso);

        return array_map(
            fn (array|CalendarResource $resource) => $resource instanceof CalendarResource
                ? $resource->toArray()
                : $resource,
            $this->resources($range),
        );
    }

    protected function resources(DateRange $_range): array
    {
        return [];
    }

    public function eventClick(string $eventId, array $eventData): void
    {
        $this->onEventClick($eventId, $eventData);
    }

    public function dateSelect(string $start, string $end, bool $allDay): void
    {
        $this->onDateSelect($start, $end, $allDay);
    }

    public function eventDrop(string $eventId, string $newStart, string $newEnd, string $rangeStart, string $rangeEnd): array
    {
        $this->onEventDrop($eventId, $newStart, $newEnd);

        return $this->fetchEvents($rangeStart, $rangeEnd);
    }

    public function eventResize(string $eventId, string $newStart, string $newEnd, string $rangeStart, string $rangeEnd): array
    {
        $this->onEventResize($eventId, $newStart, $newEnd);

        return $this->fetchEvents($rangeStart, $rangeEnd);
    }

    protected function onEventClick(string $_eventId, array $_eventData): void
    {
        //
    }

    protected function onDateSelect(string $_start, string $_end, bool $_allDay): void
    {
        //
    }

    protected function onEventDrop(string $_eventId, string $_newStart, string $_newEnd): void
    {
        //
    }

    protected function onEventResize(string $_eventId, string $_newStart, string $_newEnd): void
    {
        //
    }

    public function render(): View
    {
        return view('livewire-calendar::livewire-calendar');
    }
}
