<?php

namespace Workbench\App\Livewire\Docs;

use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Workbench\App\Models\DemoEvent;
use Workbench\App\Models\DemoResource;

class DemoCalendar extends LivewireCalendar
{
    public ?string $lastInteraction = null;

    public array $lastInteractionPayload = [];

    public array $overrides = [];

    protected function events(DateRange $_range): array
    {
        return DemoEvent::query()
            ->orderBy('start_iso')
            ->orderBy('id')
            ->get()
            ->map(fn (DemoEvent $event) => $this->toCalendarEventPayload($event))
            ->all();
    }

    protected function resources(DateRange $_range): array
    {
        return DemoResource::query()
            ->orderBy('title')
            ->orderBy('id')
            ->get()
            ->map(fn (DemoResource $resource) => [
                'id' => (string) $resource->id,
                'title' => (string) $resource->title,
                'capacity' => $resource->capacity,
                'location' => $resource->location,
            ])
            ->all();
    }

    protected function eventsForDisplay(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $events = parent::eventsForDisplay($rangeStart, $rangeEnd);

        // Apply occurrence-level overrides after recurrence expansion.
        foreach ($events as $i => $event) {
            $eventId = (string) ($event['id'] ?? '');
            if ($eventId === '') {
                continue;
            }

            $override = $this->overrides[$eventId] ?? null;
            if (! is_array($override)) {
                continue;
            }

            $start = $override['start'] ?? null;
            $end = $override['end'] ?? null;

            if (is_string($start) && $start !== '') {
                $event['start'] = $start;
            }

            if (is_string($end) && $end !== '') {
                $event['end'] = $end;
            }

            $events[$i] = $event;
        }

        return $events;
    }

    protected function onEventClick(string $eventId, array $eventData): void
    {
        $this->lastInteraction = 'eventClick';
        $this->lastInteractionPayload = [
            'eventId' => $eventId,
            'eventData' => $eventData,
        ];
    }

    protected function onDateSelect(string $start, string $end, bool $allDay): void
    {
        $this->lastInteraction = 'dateSelect';
        $this->lastInteractionPayload = [
            'start' => $start,
            'end' => $end,
            'allDay' => $allDay,
        ];
    }

    protected function onEventDrop(string $eventId, string $newStart, string $newEnd): void
    {
        $this->overrides[$eventId] = ['start' => $newStart, 'end' => $newEnd];

        $this->lastInteraction = 'eventDrop';
        $this->lastInteractionPayload = [
            'eventId' => $eventId,
            'newStart' => $newStart,
            'newEnd' => $newEnd,
        ];
    }

    protected function onEventResize(string $eventId, string $newStart, string $newEnd): void
    {
        $existing = $this->overrides[$eventId] ?? null;

        $this->overrides[$eventId] = [
            'start' => is_array($existing) ? $existing['start'] : $newStart,
            'end' => $newEnd,
        ];

        $this->lastInteraction = 'eventResize';
        $this->lastInteractionPayload = [
            'eventId' => $eventId,
            'newStart' => $newStart,
            'newEnd' => $newEnd,
        ];
    }

    public function render(): View
    {
        return view('livewire.docs.demo-calendar');
    }

    private function toCalendarEventPayload(DemoEvent $event): array
    {
        $startIso = (string) $event->start_iso;
        $endIso = (string) $event->end_iso;

        $override = $this->overrides[(string) $event->id] ?? null;
        if (is_array($override)) {
            $startIso = $override['start'];
            $endIso = $override['end'];
        }

        $payload = [
            'id' => (string) $event->id,
            'title' => (string) $event->title,
            'start' => $startIso,
            'end' => $endIso,
        ];

        if ($event->all_day) {
            $payload['allDay'] = true;
        }

        if (! is_null($event->rrule) && $event->rrule !== '') {
            $payload['rrule'] = (string) $event->rrule;
        }

        if (is_array($event->exdate_json) && count($event->exdate_json) > 0) {
            $payload['exdate'] = $event->exdate_json;
        }

        if (! is_null($event->resource_id) && $event->resource_id !== '') {
            $payload['resourceId'] = (string) $event->resource_id;
        }

        if (! is_null($event->color) && $event->color !== '') {
            $payload['color'] = (string) $event->color;
        }

        if (! is_null($event->location) && $event->location !== '') {
            $payload['location'] = (string) $event->location;
        }

        return $payload;
    }
}
