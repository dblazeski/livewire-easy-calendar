<?php

namespace Calendar\LivewireCalendar\Livewire;

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\CalendarResource;
use Calendar\LivewireCalendar\DateRange;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use RRule\RRule;
use RRule\RSet;

class LivewireCalendar extends Component
{
    public string $initialDate;

    public int $firstDay = 0;

    public string $today;

    public string $view = 'month';

    public string $timeZone;

    public array $views = [];

    public array $components = [];

    public bool $eventTimeManagementEnabled = true;

    public ?string $lecSelectedEventId = null;

    public ?string $lecTimegridSelectionStart = null;

    public ?string $lecTimegridSelectionEnd = null;

    public ?string $lecTimegridSelectionDate = null;

    public ?bool $lecTimegridSelectionAllDay = null;

    #[Computed]
    public function calendarView(): string
    {
        return $this->resolveCalendarView();
    }

    #[Computed]
    public function viewData(): array
    {
        return $this->buildViewData();
    }

    public function prev(): void
    {
        $this->clearInteractionState();

        $anchor = $this->anchorDateForView($this->view);

        $next = match ($this->view) {
            'timeGridDay', 'resourceTimelineDay', 'resourceTimeGridDay' => $anchor->copy()->subDay(),
            'timeGridWeek', 'listWeek' => $anchor->copy()->subDays(7),
            'multiMonthYear' => $anchor->copy()->subYear(),
            default => $anchor->copy()->subMonth(),
        };

        $this->initialDate = $next->toDateString();
    }

    public function next(): void
    {
        $this->clearInteractionState();

        $anchor = $this->anchorDateForView($this->view);

        $next = match ($this->view) {
            'timeGridDay', 'resourceTimelineDay', 'resourceTimeGridDay' => $anchor->copy()->addDay(),
            'timeGridWeek', 'listWeek' => $anchor->copy()->addDays(7),
            'multiMonthYear' => $anchor->copy()->addYear(),
            default => $anchor->copy()->addMonth(),
        };

        $this->initialDate = $next->toDateString();
    }

    public function goToToday(): void
    {
        $this->clearInteractionState();

        $today = Carbon::parse($this->today, $this->timeZone);

        $next = match ($this->view) {
            'timeGridDay', 'resourceTimelineDay', 'resourceTimeGridDay' => $today->copy()->startOfDay(),
            'timeGridWeek', 'listWeek' => $this->computeWeekStart($today->copy()->startOfDay(), $this->firstDay),
            'multiMonthYear' => $today->copy()->startOfYear(),
            default => $today->copy()->startOfMonth(),
        };

        $this->initialDate = $next->toDateString();
    }

    public function setView(string $view): void
    {
        $this->clearInteractionState();

        $this->view = $view;

        $current = Carbon::parse($this->initialDate, $this->timeZone);

        $next = match ($view) {
            'timeGridDay', 'resourceTimelineDay', 'resourceTimeGridDay' => $current->copy()->startOfDay(),
            'timeGridWeek', 'listWeek' => $this->computeWeekStart($current->copy()->startOfDay(), $this->firstDay),
            'multiMonthYear' => $current->copy()->startOfYear(),
            default => $current->copy()->startOfMonth(),
        };

        $this->initialDate = $next->toDateString();
    }

    public function mount(?string $initialDate = null, int $firstDay = 0, ?string $today = null, ?string $view = null, ?string $timeZone = null, ?array $views = null, ?array $components = null, ?bool $eventTimeManagementEnabled = null): void
    {
        $this->initialDate = $initialDate ?? now()->format('Y-m-d');
        $this->firstDay = $firstDay;
        $this->today = $today ?? now()->format('Y-m-d');
        $this->view = $view ?? 'month';

        $this->timeZone = $timeZone ?? config('app.timezone', 'UTC');

        $this->views = is_array($views) ? $views : [];

        $this->components = is_array($components) ? $components : [];

        $this->eventTimeManagementEnabled = $eventTimeManagementEnabled ?? true;
    }

    public function componentView(string $key): string
    {
        $custom = $this->components[$key]
            ?? config("livewire-calendar.components.{$key}")
            ?? null;

        if (is_string($custom) && $custom !== '' && view()->exists($custom)) {
            return $custom;
        }

        return match ($key) {
            'header' => 'livewire-calendar::components.header',
            'header-nav-prev' => 'livewire-calendar::components.header.nav-prev',
            'header-nav-today' => 'livewire-calendar::components.header.nav-today',
            'header-nav-next' => 'livewire-calendar::components.header.nav-next',
            'header-view-month' => 'livewire-calendar::components.header.view-month',
            'header-view-timeGridWeek' => 'livewire-calendar::components.header.view-time-grid-week',
            'header-view-timeGridDay' => 'livewire-calendar::components.header.view-time-grid-day',
            'header-view-listWeek' => 'livewire-calendar::components.header.view-list-week',
            'header-view-multiMonthYear' => 'livewire-calendar::components.header.view-multi-month-year',
            'header-view-resourceTimelineDay' => 'livewire-calendar::components.header.view-resource-timeline-day',
            'header-view-resourceTimeGridDay' => 'livewire-calendar::components.header.view-resource-time-grid-day',
            default => 'livewire-calendar::components.header',
        };
    }

    protected function resolveCalendarView(): string
    {
        $custom = $this->views[$this->view]
            ?? config("livewire-calendar.views.{$this->view}")
            ?? null;
        if (is_string($custom) && $custom !== '' && view()->exists($custom)) {
            return $custom;
        }

        return match ($this->view) {
            'timeGridWeek' => 'livewire-calendar::views.time-grid-week',
            'timeGridDay' => 'livewire-calendar::views.time-grid-day',
            'listWeek' => 'livewire-calendar::views.list-week',
            'multiMonthYear' => 'livewire-calendar::views.multi-month-year',
            'resourceTimelineDay' => 'livewire-calendar::views.resource-timeline-day',
            'resourceTimeGridDay' => 'livewire-calendar::views.resource-timegrid-day',
            default => 'livewire-calendar::views.month',
        };
    }

    protected function buildViewData(): array
    {
        $anchor = $this->anchorDateForView($this->view);

        $data = match ($this->view) {
            'timeGridWeek' => $this->buildTimeGridViewData($anchor, 7),
            'timeGridDay' => $this->buildTimeGridViewData($anchor, 1),
            'listWeek' => $this->buildListWeekViewData($anchor),
            'multiMonthYear' => $this->buildMultiMonthYearViewData($anchor),
            'resourceTimelineDay' => $this->buildResourceTimelineDayViewData($anchor),
            'resourceTimeGridDay' => $this->buildResourceTimeGridDayViewData($anchor),
            default => $this->buildMonthViewData($anchor),
        };

        return [
            ...$data,
            'lecSelectedEventId' => $this->lecSelectedEventId,
            'lecTimegridSelectionStart' => $this->lecTimegridSelectionStart,
            'lecTimegridSelectionEnd' => $this->lecTimegridSelectionEnd,
            'lecTimegridSelectionDate' => $this->lecTimegridSelectionDate,
            'lecTimegridSelectionAllDay' => $this->lecTimegridSelectionAllDay,
        ];
    }

    protected function buildMonthViewData(Carbon $monthStart): array
    {
        $gridStart = $this->computeGridStart($monthStart, $this->firstDay);
        $rangeStart = $gridStart->copy()->startOfDay();
        $rangeEnd = $gridStart->copy()->addDays(42)->startOfDay();

        $events = $this->eventsForDisplay($rangeStart, $rangeEnd);

        $eventsByDate = [];
        foreach ($events as $event) {
            $eventStart = $this->parseIsoInZone((string) ($event['start'] ?? ''));
            $dateKey = $eventStart->toDateString();
            $eventsByDate[$dateKey] ??= [];
            $eventsByDate[$dateKey][] = $event;
        }

        $days = [];
        $currentMonth = (int) $monthStart->month;
        for ($i = 0; $i < 42; $i++) {
            $cellDate = $gridStart->copy()->addDays($i);
            $days[] = [
                'date' => $cellDate->toDateString(),
                'day' => (int) $cellDate->day,
                'currentMonth' => $cellDate->month === $currentMonth,
            ];
        }

        return [
            'title' => $monthStart->format('F Y'),
            'weekdayNames' => $this->orderedWeekdayNames($this->firstDay),
            'days' => $days,
            'eventsByDate' => $eventsByDate,
            'rangeStart' => $rangeStart->toDateString(),
            'rangeEnd' => $rangeEnd->toDateString(),
        ];
    }

    protected function buildTimeGridViewData(Carbon $anchorDay, int $dayCount): array
    {
        $days = [];
        for ($i = 0; $i < $dayCount; $i++) {
            $days[] = $anchorDay->copy()->addDays($i)->startOfDay();
        }

        $rangeStart = $days[0]->copy()->startOfDay();
        $rangeEnd = $days[count($days) - 1]->copy()->startOfDay()->addDay();

        $events = $this->eventsForDisplay($rangeStart, $rangeEnd);

        $allDayEvents = [];
        $timedEvents = [];
        foreach ($events as $event) {
            if (($event['allDay'] ?? false) === true) {
                $allDayEvents[] = $event;
            } else {
                $timedEvents[] = $event;
            }
        }

        $alldayEventsByDate = [];
        foreach ($days as $day) {
            $alldayEventsByDate[$day->toDateString()] = [];
        }

        foreach ($allDayEvents as $event) {
            $eventStart = $this->parseIsoInZone((string) ($event['start'] ?? ''));
            $eventEnd = $this->parseIsoInZone((string) ($event['end'] ?? ''));

            foreach ($days as $day) {
                $dayStart = $day->copy()->startOfDay();
                $dayEnd = $dayStart->copy()->addDay();

                if ($eventStart->gte($dayEnd) || $eventEnd->lte($dayStart)) {
                    continue;
                }

                $alldayEventsByDate[$dayStart->toDateString()][] = $event;
            }
        }

        $timedSegmentsByDate = [];
        foreach ($days as $day) {
            $timedSegmentsByDate[$day->toDateString()] = [];
        }

        foreach ($timedEvents as $event) {
            $eventStart = $this->parseIsoInZone((string) ($event['start'] ?? ''));
            $eventEnd = $this->parseIsoInZone((string) ($event['end'] ?? ''));

            foreach ($days as $day) {
                $dayStart = $day->copy()->startOfDay();
                $dayEnd = $dayStart->copy()->addDay();

                if ($eventStart->gte($dayEnd) || $eventEnd->lte($dayStart)) {
                    continue;
                }

                $segStart = $eventStart->lt($dayStart) ? $dayStart : $eventStart;
                $segEnd = $eventEnd->gt($dayEnd) ? $dayEnd : $eventEnd;

                $startMin = $this->wallClockMinuteInDayGrid($segStart, $dayStart, $dayEnd);
                $endMin = $this->wallClockMinuteInDayGrid($segEnd, $dayStart, $dayEnd);

                $dateStr = $dayStart->toDateString();
                $timedSegmentsByDate[$dateStr][] = [
                    'event' => $event,
                    'date' => $dateStr,
                    'startMin' => $startMin,
                    'endMin' => $endMin,
                    'topPercent' => ($startMin / 1440) * 100,
                    'heightPercent' => (($endMin - $startMin) / 1440) * 100,
                ];
            }
        }

        $title = $dayCount === 1
            ? $anchorDay->format('F j, Y')
            : $this->formatWeekRangeTitle($anchorDay, $anchorDay->copy()->addDays(6));

        return [
            'title' => $title,
            'days' => array_map(fn (Carbon $d) => [
                'date' => $d->toDateString(),
                'label' => $d->format('D j'),
            ], $days),
            'rangeStart' => $rangeStart->toDateString(),
            'rangeEnd' => $rangeEnd->toDateString(),
            'dayCount' => $dayCount,
            'alldayEventsByDate' => $alldayEventsByDate,
            'timedSegmentsByDate' => $timedSegmentsByDate,
        ];
    }

    protected function buildListWeekViewData(Carbon $weekStart): array
    {
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $weekStart->copy()->addDays($i)->startOfDay();
        }

        $rangeStart = $weekStart->copy()->startOfDay();
        $rangeEnd = $weekStart->copy()->addDays(7)->startOfDay();

        $events = $this->eventsForDisplay($rangeStart, $rangeEnd);
        $events = $this->sortEventsByStartEndId($events);

        $dayMap = [];
        foreach ($days as $day) {
            $dayMap[$day->toDateString()] = [];
        }

        foreach ($events as $event) {
            $eventStart = Carbon::parse((string) ($event['start'] ?? ''));
            $eventEnd = Carbon::parse((string) ($event['end'] ?? ''));

            foreach ($days as $day) {
                $dayStart = $day->copy()->startOfDay();
                $dayEnd = $dayStart->copy()->addDay();
                $dateStr = $dayStart->toDateString();

                if ($eventStart->gte($dayEnd) || $eventEnd->lte($dayStart)) {
                    continue;
                }

                $dayMap[$dateStr][] = $event;
            }
        }

        $dayGroups = [];
        foreach ($dayMap as $dateStr => $dayEvents) {
            if (count($dayEvents) === 0) {
                continue;
            }

            $seenIds = [];
            $rows = [];
            foreach ($dayEvents as $event) {
                $id = (string) ($event['id'] ?? '');
                if ($id === '' || isset($seenIds[$id])) {
                    continue;
                }
                $seenIds[$id] = true;

                $rows[] = $event;
            }

            $headingDate = Carbon::createFromFormat('Y-m-d', $dateStr, $this->timeZone);
            $dayGroups[] = [
                'date' => $dateStr,
                'heading' => $headingDate->format('l, F j, Y'),
                'events' => $rows,
            ];
        }

        $title = $this->formatWeekRangeTitle($weekStart, $weekStart->copy()->addDays(6));

        return [
            'title' => $title,
            'rangeStart' => $rangeStart->toDateString(),
            'rangeEnd' => $rangeEnd->toDateString(),
            'dayGroups' => $dayGroups,
        ];
    }

    protected function buildMultiMonthYearViewData(Carbon $yearStart): array
    {
        $months = [];
        $weekdayNames = $this->orderedWeekdayNames($this->firstDay);

        for ($m = 0; $m < 12; $m++) {
            $monthStart = $yearStart->copy()->addMonths($m)->startOfMonth();
            $gridStart = $this->computeGridStart($monthStart, $this->firstDay);
            $currentMonth = (int) $monthStart->month;

            $cells = [];
            for ($i = 0; $i < 42; $i++) {
                $cellDate = $gridStart->copy()->addDays($i);
                $cells[] = [
                    'date' => $cellDate->toDateString(),
                    'day' => (int) $cellDate->day,
                    'currentMonth' => $cellDate->month === $currentMonth,
                ];
            }

            $months[] = [
                'key' => $monthStart->format('Y-m'),
                'title' => $monthStart->format('F'),
                'weekdayNames' => $weekdayNames,
                'cells' => $cells,
            ];
        }

        return [
            'title' => $yearStart->format('Y'),
            'months' => $months,
        ];
    }

    protected function buildResourceTimelineDayViewData(Carbon $dayDate): array
    {
        $rangeStart = $dayDate->copy()->startOfDay();
        $rangeEnd = $rangeStart->copy()->addDay();

        $range = new DateRange(start: $rangeStart, end: $rangeEnd);

        $resources = array_map(
            fn (array|CalendarResource $resource) => $resource instanceof CalendarResource
                ? $resource->toArray()
                : $resource,
            $this->resources($range),
        );

        $events = $this->eventsForDisplay($rangeStart, $rangeEnd);

        $eventsByResource = [];
        foreach ($resources as $resource) {
            $resourceId = (string) ($resource['id'] ?? '');
            if ($resourceId !== '') {
                $eventsByResource[$resourceId] = [];
            }
        }

        foreach ($events as $event) {
            $resourceIdRaw = $event['resourceId'] ?? null;
            if ($resourceIdRaw === null) {
                continue;
            }

            $resourceId = (string) $resourceIdRaw;
            if (! array_key_exists($resourceId, $eventsByResource)) {
                continue;
            }

            $eventStart = $this->parseIsoInZone((string) ($event['start'] ?? ''));
            $eventEnd = $this->parseIsoInZone((string) ($event['end'] ?? ''));

            if ($eventStart->gte($rangeEnd) || $eventEnd->lte($rangeStart)) {
                continue;
            }

            $segStart = $eventStart->lt($rangeStart) ? $rangeStart : $eventStart;
            $segEnd = $eventEnd->gt($rangeEnd) ? $rangeEnd : $eventEnd;

            $startMin = $this->wallClockMinuteInDayGrid($segStart, $rangeStart, $rangeEnd);
            $endMin = $this->wallClockMinuteInDayGrid($segEnd, $rangeStart, $rangeEnd);
            if ($endMin <= $startMin) {
                continue;
            }

            $eventsByResource[$resourceId][] = [
                'event' => $event,
                'resourceId' => $resourceId,
                'startMin' => $startMin,
                'endMin' => $endMin,
                'leftPercent' => ($startMin / 1440) * 100,
                'widthPercent' => (($endMin - $startMin) / 1440) * 100,
            ];
        }

        return [
            'title' => $dayDate->format('F j, Y'),
            'date' => $dayDate->toDateString(),
            'resources' => $resources,
            'eventsByResource' => $eventsByResource,
            'rangeStart' => $rangeStart->toDateString(),
            'rangeEnd' => $rangeEnd->toDateString(),
        ];
    }

    protected function buildResourceTimeGridDayViewData(Carbon $dayDate): array
    {
        $rangeStart = $dayDate->copy()->startOfDay();
        $rangeEnd = $rangeStart->copy()->addDay();

        $range = new DateRange(start: $rangeStart, end: $rangeEnd);

        $resources = array_map(
            fn (array|CalendarResource $resource) => $resource instanceof CalendarResource
                ? $resource->toArray()
                : $resource,
            $this->resources($range),
        );

        $resourceIds = [];
        foreach ($resources as $resource) {
            $resourceId = (string) ($resource['id'] ?? '');
            if ($resourceId !== '') {
                $resourceIds[$resourceId] = true;
            }
        }

        $alldayEventsByResourceId = [];
        $timedSegmentsByResourceId = [];
        foreach (array_keys($resourceIds) as $resourceId) {
            $alldayEventsByResourceId[$resourceId] = [];
            $timedSegmentsByResourceId[$resourceId] = [];
        }

        $events = $this->eventsForDisplay($rangeStart, $rangeEnd);
        foreach ($events as $event) {
            $resourceIdRaw = $event['resourceId'] ?? null;
            if ($resourceIdRaw === null) {
                continue;
            }

            $resourceId = (string) $resourceIdRaw;
            if (! array_key_exists($resourceId, $timedSegmentsByResourceId)) {
                continue;
            }

            $eventStart = $this->parseIsoInZone((string) ($event['start'] ?? ''));
            $eventEnd = $this->parseIsoInZone((string) ($event['end'] ?? ''));
            if ($eventStart->gte($rangeEnd) || $eventEnd->lte($rangeStart)) {
                continue;
            }

            if (($event['allDay'] ?? false) === true) {
                $alldayEventsByResourceId[$resourceId][] = $event;

                continue;
            }

            $segStart = $eventStart->lt($rangeStart) ? $rangeStart : $eventStart;
            $segEnd = $eventEnd->gt($rangeEnd) ? $rangeEnd : $eventEnd;

            $startMin = $this->wallClockMinuteInDayGrid($segStart, $rangeStart, $rangeEnd);
            $endMin = $this->wallClockMinuteInDayGrid($segEnd, $rangeStart, $rangeEnd);
            if ($endMin <= $startMin) {
                continue;
            }

            $timedSegmentsByResourceId[$resourceId][] = [
                'event' => $event,
                'resourceId' => $resourceId,
                'date' => $rangeStart->toDateString(),
                'startMin' => $startMin,
                'endMin' => $endMin,
                'topPercent' => ($startMin / 1440) * 100,
                'heightPercent' => (($endMin - $startMin) / 1440) * 100,
            ];
        }

        return [
            'title' => $dayDate->format('F j, Y'),
            'date' => $rangeStart->toDateString(),
            'resources' => $resources,
            'rangeStart' => $rangeStart->toDateString(),
            'rangeEnd' => $rangeEnd->toDateString(),
            'alldayEventsByResourceId' => $alldayEventsByResourceId,
            'timedSegmentsByResourceId' => $timedSegmentsByResourceId,
        ];
    }

    protected function eventsForDisplay(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $range = new DateRange(start: $rangeStart, end: $rangeEnd);

        $events = array_map(
            fn (array|CalendarEvent $event) => $event instanceof CalendarEvent
                ? $event->toArray()
                : $event,
            $this->events($range),
        );

        return $this->expandRecurringEvents($events, $rangeStart, $rangeEnd);
    }

    protected function expandRecurringEvents(array $events, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $expanded = [];

        foreach ($events as $event) {
            $rruleValue = $event['rrule'] ?? null;
            if (! is_string($rruleValue) || trim($rruleValue) === '') {
                $expanded[] = $event;

                continue;
            }

            $baseStartIso = (string) ($event['start'] ?? '');
            $baseEndIso = (string) ($event['end'] ?? '');

            $baseStart = $this->parseIsoInZone($baseStartIso);
            $baseEnd = $this->parseIsoInZone($baseEndIso);
            if (! $baseStart->isValid() || ! $baseEnd->isValid()) {
                $expanded[] = $event;

                continue;
            }

            $dayDelta = $baseStart->copy()->startOfDay()->diffInDays($baseEnd->copy()->startOfDay(), false);
            $durationSeconds = $baseEnd->diffInSeconds($baseStart);

            $rule = $this->extractRRuleValue($rruleValue);
            $rset = new RSet;
            $rset->addRRule(new RRule($rule, $baseStart->toDateTime()));

            $exdate = $event['exdate'] ?? null;
            if (is_array($exdate)) {
                foreach ($exdate as $ex) {
                    if (! is_string($ex) || trim($ex) === '') {
                        continue;
                    }

                    $exDt = $this->parseExdate($ex, $baseStart);
                    if (! $exDt->isValid()) {
                        continue;
                    }

                    $rset->addExDate($exDt->toDateTime());
                }
            }

            $occurrences = $rset->getOccurrencesBetween($rangeStart->toDateTime(), $rangeEnd->toDateTime());
            foreach ($occurrences as $occurrence) {
                $occStart = Carbon::instance($occurrence)->setTimezone($this->timeZone);
                if ($occStart->lt($rangeStart) || $occStart->gte($rangeEnd)) {
                    continue;
                }

                $occEnd = $dayDelta >= 0
                    ? $occStart->copy()->addDays($dayDelta)->setTime($baseEnd->hour, $baseEnd->minute, $baseEnd->second)
                    : $occStart->copy()->addSeconds($durationSeconds);

                $occurrenceKey = $this->isDateOnlyIso($baseStartIso)
                    ? $occStart->format('Ymd')
                    : $occStart->format('Ymd\\THis');

                $occurrenceId = ((string) ($event['id'] ?? '')).'__'.$occurrenceKey;

                $expanded[] = [
                    ...$event,
                    'id' => $occurrenceId,
                    'recurrenceId' => (string) ($event['id'] ?? ''),
                    'start' => $occStart->toIso8601String(),
                    'end' => $occEnd->toIso8601String(),
                ];
            }
        }

        return $expanded;
    }

    protected function extractRRuleValue(string $rruleValue): string
    {
        $trimmed = trim($rruleValue);
        $lines = preg_split("/\r?\n/", $trimmed) ?: [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            if (str_starts_with(strtoupper($line), 'RRULE:')) {
                return substr($line, strlen('RRULE:'));
            }
        }

        if (str_starts_with(strtoupper($trimmed), 'RRULE:')) {
            return substr($trimmed, strlen('RRULE:'));
        }

        return $trimmed;
    }

    protected function parseExdate(string $exdateValue, Carbon $dtstart): Carbon
    {
        $exdateValue = trim($exdateValue);

        if ($this->isDateOnlyIso($exdateValue)) {
            return Carbon::createFromFormat('Y-m-d', $exdateValue, $this->timeZone)->setTime(
                $dtstart->hour,
                $dtstart->minute,
                $dtstart->second,
            );
        }

        return Carbon::parse($exdateValue)->setTimezone($this->timeZone);
    }

    protected function sortEventsByStartEndId(array $events): array
    {
        usort($events, function (array $a, array $b): int {
            $aStart = Carbon::parse((string) ($a['start'] ?? ''))->getTimestamp();
            $bStart = Carbon::parse((string) ($b['start'] ?? ''))->getTimestamp();
            if ($aStart !== $bStart) {
                return $aStart <=> $bStart;
            }

            $aEnd = Carbon::parse((string) ($a['end'] ?? ''))->getTimestamp();
            $bEnd = Carbon::parse((string) ($b['end'] ?? ''))->getTimestamp();
            if ($aEnd !== $bEnd) {
                return $aEnd <=> $bEnd;
            }

            return strcmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
        });

        return $events;
    }

    protected function formatWeekRangeTitle(Carbon $weekStart, Carbon $weekEnd): string
    {
        if ((int) $weekStart->month === (int) $weekEnd->month) {
            return $weekStart->format('F j').' – '.$weekEnd->format('j, Y');
        }

        if ((int) $weekStart->year === (int) $weekEnd->year) {
            return $weekStart->format('M j').' – '.$weekEnd->format('M j, Y');
        }

        return $weekStart->format('M j, Y').' – '.$weekEnd->format('M j, Y');
    }

    protected function orderedWeekdayNames(int $firstDay): array
    {
        $names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $ordered = [];
        for ($i = 0; $i < 7; $i++) {
            $ordered[] = $names[($firstDay + $i) % 7];
        }

        return $ordered;
    }

    protected function computeGridStart(Carbon $date, int $firstDay): Carbon
    {
        $daysBack = ((int) $date->dayOfWeek - $firstDay + 7) % 7;

        return $date->copy()->subDays($daysBack);
    }

    protected function computeWeekStart(Carbon $date, int $firstDay): Carbon
    {
        return $this->computeGridStart($date->copy()->startOfDay(), $firstDay);
    }

    protected function anchorDateForView(string $view): Carbon
    {
        $initial = Carbon::parse($this->initialDate, $this->timeZone);

        return match ($view) {
            'timeGridDay', 'resourceTimelineDay', 'resourceTimeGridDay' => $initial->copy()->startOfDay(),
            'timeGridWeek', 'listWeek' => $this->computeWeekStart($initial->copy()->startOfDay(), $this->firstDay),
            'multiMonthYear' => $initial->copy()->startOfYear(),
            default => $initial->copy()->startOfMonth(),
        };
    }

    protected function wallClockMinuteInDayGrid(Carbon $dt, Carbon $dayStart, Carbon $dayEnd): int
    {
        if ($dt->lte($dayStart)) {
            return 0;
        }

        if ($dt->gte($dayEnd)) {
            return 1440;
        }

        return ((int) $dt->hour * 60) + (int) $dt->minute;
    }

    protected function parseIsoInZone(string $iso): Carbon
    {
        $iso = trim($iso);

        if ($iso === '') {
            return Carbon::now($this->timeZone);
        }

        if ($this->isDateOnlyIso($iso)) {
            return Carbon::createFromFormat('Y-m-d', $iso, $this->timeZone)->startOfDay();
        }

        return Carbon::parse($iso)->setTimezone($this->timeZone);
    }

    protected function isDateOnlyIso(string $iso): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso) === 1;
    }

    #[Renderless]
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

    #[Renderless]
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

    protected function clearInteractionState(): void
    {
        $this->lecSelectedEventId = null;
        $this->lecTimegridSelectionStart = null;
        $this->lecTimegridSelectionEnd = null;
        $this->lecTimegridSelectionDate = null;
        $this->lecTimegridSelectionAllDay = null;
    }

    public function eventClick(string $eventId, array $eventData): void
    {
        $this->lecTimegridSelectionStart = null;
        $this->lecTimegridSelectionEnd = null;
        $this->lecTimegridSelectionDate = null;
        $this->lecTimegridSelectionAllDay = null;

        if ($this->lecSelectedEventId === $eventId) {
            $this->lecSelectedEventId = null;
        } else {
            $this->lecSelectedEventId = $eventId;
        }

        $this->onEventClick($eventId, $eventData);
    }

    public function dateSelect(string $start, string $end, bool $allDay): void
    {
        $this->lecSelectedEventId = null;

        $this->lecTimegridSelectionStart = $start;
        $this->lecTimegridSelectionEnd = $end;
        $this->lecTimegridSelectionAllDay = $allDay;
        $this->lecTimegridSelectionDate = strlen($start) >= 10 ? substr($start, 0, 10) : null;

        $this->onDateSelect($start, $end, $allDay);
    }

    public function eventDrop(string $eventId, string $newStart, string $newEnd, string $rangeStart, string $rangeEnd): array
    {
        if (! $this->eventTimeManagementEnabled) {
            return $this->fetchEvents($rangeStart, $rangeEnd);
        }

        $this->clearInteractionState();

        $this->onEventDrop($eventId, $newStart, $newEnd);

        return $this->fetchEvents($rangeStart, $rangeEnd);
    }

    public function eventResize(string $eventId, string $newStart, string $newEnd, string $rangeStart, string $rangeEnd): array
    {
        if (! $this->eventTimeManagementEnabled) {
            return $this->fetchEvents($rangeStart, $rangeEnd);
        }

        $this->clearInteractionState();

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
