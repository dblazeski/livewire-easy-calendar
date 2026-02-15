<?php

namespace Calendar\LivewireCalendar;

use Illuminate\Support\Carbon;

final readonly class DateRange
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
    ) {}

    public static function fromIso(string $startIso, string $endIso): self
    {
        return new self(
            start: Carbon::parse($startIso),
            end: Carbon::parse($endIso),
        );
    }
}
