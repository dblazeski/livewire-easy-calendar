@php
    $dayGroups = is_array($dayGroups ?? null) ? $dayGroups : [];
    $rangeStart = (string) ($rangeStart ?? '');
    $rangeEnd = (string) ($rangeEnd ?? '');
@endphp

<div
    class="lec-list-view"
    data-testid="list-view"
    data-range-start="{{ $rangeStart }}"
    data-range-end="{{ $rangeEnd }}"
>
    @foreach ($dayGroups as $group)
        @php
            $dateStr = (string) ($group['date'] ?? '');
            $heading = (string) ($group['heading'] ?? '');
            $events = is_array($group['events'] ?? null) ? $group['events'] : [];
        @endphp

        <div class="lec-list-day-group" data-testid="list-day-{{ $dateStr }}" data-date="{{ $dateStr }}">
            <div class="lec-list-day-heading">{{ $heading }}</div>

            @foreach ($events as $event)
                @php
                    $eventId = (string) ($event['id'] ?? '');
                    $startIso = (string) ($event['start'] ?? '');
                    $endIso = (string) ($event['end'] ?? '');
                    $allDay = ($event['allDay'] ?? false) === true;

                    $timeLabel = 'all-day';
                    if (! $allDay && $startIso !== '' && $endIso !== '') {
                        $start = \Illuminate\Support\Carbon::parse($startIso)->setTimezone($this->timeZone);
                        $end = \Illuminate\Support\Carbon::parse($endIso)->setTimezone($this->timeZone);
                        $timeLabel = $start->format('H:i').' - '.$end->format('H:i');
                    }
                @endphp

                <div
                    class="lec-list-event"
                    data-testid="list-event-{{ $eventId }}"
                    data-event-id="{{ $eventId }}"
                    data-date="{{ $dateStr }}"
                    data-event-start="{{ $startIso }}"
                    data-event-end="{{ $endIso }}"
                    @if ($allDay)
                        data-all-day="true"
                    @endif
                >
                    <span class="lec-list-event-time">{{ $timeLabel }}</span>
                    <span class="lec-list-event-title">{{ (string) ($event['title'] ?? '') }}</span>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
