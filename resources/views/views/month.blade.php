<div class="lec-month-grid" data-testid="month-grid">
    @foreach (($weekdayNames ?? []) as $name)
        <div class="lec-dow-header">{{ $name }}</div>
    @endforeach

    @foreach (($days ?? []) as $day)
        @php
            $dateStr = (string) ($day['date'] ?? '');
            $isCurrentMonth = ($day['currentMonth'] ?? false) === true;
            $cellEvents = is_string($dateStr) && $dateStr !== '' ? ($eventsByDate[$dateStr] ?? []) : [];
        @endphp

        <div
            class="lec-day-cell{{ $isCurrentMonth ? '' : ' lec-day-cell--outside' }}"
            data-testid="day-cell-{{ $dateStr }}"
            data-date="{{ $dateStr }}"
            data-current-month="{{ $isCurrentMonth ? 'true' : 'false' }}"
        >
            <span class="lec-day-number">{{ (int) ($day['day'] ?? 0) }}</span>

            @foreach ($cellEvents as $event)
                @php
                    $eventId = (string) ($event['id'] ?? '');
                    $eventAllDay = ($event['allDay'] ?? false) === true;
                    $bgCandidate = (string) ($event['backgroundColor'] ?? ($event['color'] ?? ''));
                    $bgCandidate = trim($bgCandidate);
                    $bgHex = preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $bgCandidate) === 1
                        ? $bgCandidate
                        : '';
                @endphp

                <div
                    class="lec-event"
                    data-testid="month-event-{{ $eventId }}-{{ $dateStr }}"
                    data-event-id="{{ $eventId }}"
                    data-event-start="{{ (string) ($event['start'] ?? '') }}"
                    data-event-end="{{ (string) ($event['end'] ?? '') }}"
                    @if ($bgHex !== '')
                        style="--lec-event-bg: {{ $bgHex }};"
                    @endif
                    @if ($eventAllDay)
                        data-all-day="true"
                    @endif
                >
                    {{ (string) ($event['title'] ?? '') }}
                </div>
            @endforeach
        </div>
    @endforeach
</div>
