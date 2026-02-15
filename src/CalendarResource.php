<?php

namespace Calendar\LivewireCalendar;

final readonly class CalendarResource
{
    public function __construct(
        public string $id,
        public string $title,
        public array $extra = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            ...$this->extra,
        ];
    }
}
