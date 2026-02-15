<?php

namespace Calendar\LivewireCalendar;

use Illuminate\Support\Carbon;

final readonly class CalendarEvent
{
    public function __construct(
        public string $id,
        public string $title,
        public Carbon $start,
        public Carbon $end,
        public array $extra = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
            ...$this->extra,
        ];
    }
}
