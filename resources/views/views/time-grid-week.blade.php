@php
    $days = is_array($days ?? null) ? $days : [];
    $alldayEventsByDate = is_array($alldayEventsByDate ?? null) ? $alldayEventsByDate : [];
    $timedSegmentsByDate = is_array($timedSegmentsByDate ?? null) ? $timedSegmentsByDate : [];

    $dayCount = count($days);

    $selectedEventId = $lecSelectedEventId ?? null;
    $selectionStart = $lecTimegridSelectionStart ?? null;
    $selectionEnd = $lecTimegridSelectionEnd ?? null;
    $selectionDate = $lecTimegridSelectionDate ?? null;
@endphp

<div
    class="lec-timegrid"
    data-testid="timegrid"
    style="--lec-day-count: {{ $dayCount }}"
>
    <div class="lec-timegrid-header">
        <div class="lec-timegrid-gutter"></div>

        @foreach ($days as $day)
            @php
                $dateStr = (string) ($day['date'] ?? '');
            @endphp

            <div class="lec-timegrid-day-header" data-testid="timegrid-day-{{ $dateStr }}">
                {{ (string) ($day['label'] ?? '') }}
            </div>
        @endforeach
    </div>

    <div class="lec-allday-row" data-testid="allday-row">
        <div class="lec-timegrid-gutter lec-allday-label">all-day</div>

        @foreach ($days as $day)
            @php
                $dateStr = (string) ($day['date'] ?? '');
                $cellEvents = $dateStr !== '' ? ($alldayEventsByDate[$dateStr] ?? []) : [];
            @endphp

            <div class="lec-allday-cell" data-testid="allday-cell-{{ $dateStr }}" data-date="{{ $dateStr }}">
                @foreach ($cellEvents as $event)
                    @php
                        $eventId = (string) ($event['id'] ?? '');
                    @endphp

                    <div
                        class="lec-allday-event"
                        data-testid="allday-event-{{ $eventId }}-{{ $dateStr }}"
                        data-event-id="{{ $eventId }}"
                        data-date="{{ $dateStr }}"
                    >
                        {{ (string) ($event['title'] ?? '') }}
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="lec-timegrid-body">
        <div class="lec-timegrid-body-inner">
            @for ($minutes = 0; $minutes < 1440; $minutes += 30)
                @php
                    $hours = intdiv($minutes, 60);
                    $mins = $minutes % 60;
                    $timeStr = str_pad((string) $hours, 2, '0', STR_PAD_LEFT).':'.str_pad((string) $mins, 2, '0', STR_PAD_LEFT);
                @endphp

                <div class="lec-timegrid-slot">
                    <div
                        class="lec-time-label"
                        @if ($mins === 0)
                            data-testid="time-label-{{ $timeStr }}"
                        @endif
                    >
                        @if ($mins === 0)
                            {{ $timeStr }}
                        @endif
                    </div>

                    @foreach ($days as $day)
                        @php
                            $dateStr = (string) ($day['date'] ?? '');
                        @endphp

                        <div
                            class="lec-slot-cell"
                            data-testid="slot-cell-{{ $dateStr }}-{{ $timeStr }}"
                            data-date="{{ $dateStr }}"
                            data-minute="{{ $minutes }}"
                        ></div>
                    @endforeach
                </div>
            @endfor

            <div class="lec-timegrid-events-layer">
                @foreach ($days as $day)
                    @php
                        $dateStr = (string) ($day['date'] ?? '');
                        $segments = $dateStr !== '' ? ($timedSegmentsByDate[$dateStr] ?? []) : [];
                    @endphp

                    <div class="lec-timegrid-day-body" data-testid="timegrid-day-body-{{ $dateStr }}" data-date="{{ $dateStr }}">
                        @php
                            $selStartMin = null;
                            $selEndMin = null;
                            $showSelection = is_string($selectionDate)
                                && $selectionDate !== ''
                                && $selectionDate === $dateStr
                                && is_string($selectionStart)
                                && $selectionStart !== ''
                                && is_string($selectionEnd)
                                && $selectionEnd !== '';
                            if ($showSelection && strlen($selectionStart) >= 16 && strlen($selectionEnd) >= 16) {
                                $selStartTime = substr($selectionStart, 11, 5);
                                $selEndTime = substr($selectionEnd, 11, 5);

                                $selStartParts = explode(':', $selStartTime);
                                $selEndParts = explode(':', $selEndTime);
                                if (count($selStartParts) === 2 && count($selEndParts) === 2) {
                                    $selStartMin = ((int) $selStartParts[0] * 60) + (int) $selStartParts[1];
                                    $selEndMin = ((int) $selEndParts[0] * 60) + (int) $selEndParts[1];
                                    if ($selEndMin <= $selStartMin) {
                                        $showSelection = false;
                                    }
                                } else {
                                    $showSelection = false;
                                }
                            }
                        @endphp

                        @if ($showSelection)
                            <div
                                class="lec-timegrid-selection"
                                data-testid="timegrid-selection"
                                data-start="{{ $selectionStart }}"
                                data-end="{{ $selectionEnd }}"
                                style="--lec-start-min: {{ (int) $selStartMin }}; --lec-end-min: {{ (int) $selEndMin }}"
                            ></div>
                        @endif

                        @foreach ($segments as $seg)
                            @php
                                $event = is_array($seg['event'] ?? null) ? $seg['event'] : [];
                                $eventId = (string) ($event['id'] ?? '');
                                $isSelected = is_string($selectedEventId) && $selectedEventId !== '' && $selectedEventId === $eventId;
                                $startMin = (int) ($seg['startMin'] ?? 0);
                                $endMin = (int) ($seg['endMin'] ?? 0);
                            @endphp

                            <div
                                class="lec-timed-event{{ $isSelected ? ' lec-event--selected' : '' }}"
                                data-testid="timed-event-{{ $eventId }}-{{ $dateStr }}"
                                data-event-id="{{ $eventId }}"
                                data-date="{{ $dateStr }}"
                                data-start-min="{{ $startMin }}"
                                data-end-min="{{ $endMin }}"
                                data-col="0"
                                data-col-count="1"
                                data-event-start="{{ (string) ($event['start'] ?? '') }}"
                                data-event-end="{{ (string) ($event['end'] ?? '') }}"
                                style="--lec-start-min: {{ $startMin }}; --lec-end-min: {{ $endMin }}"
                                @if ($isSelected)
                                    data-selected="true"
                                @endif
                            >
                                <span class="lec-timed-event-title">{{ (string) ($event['title'] ?? '') }}</span>
                                <div class="lec-resize-handle" data-testid="timed-event-resize-handle-{{ $eventId }}-{{ $dateStr }}"></div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
