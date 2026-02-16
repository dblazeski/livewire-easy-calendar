@php
    $dateStr = (string) ($date ?? '');
    $resources = is_array($resources ?? null) ? $resources : [];
    $alldayEventsByResourceId = is_array($alldayEventsByResourceId ?? null) ? $alldayEventsByResourceId : [];
    $timedSegmentsByResourceId = is_array($timedSegmentsByResourceId ?? null) ? $timedSegmentsByResourceId : [];

    $resourceCount = count($resources);
    $selectedEventId = $lecSelectedEventId ?? null;
@endphp

<div
    class="lec-timegrid lec-resource-timegrid"
    data-testid="resource-timegrid"
    style="--lec-day-count: {{ $resourceCount }}"
>
    <div class="lec-timegrid-header">
        <div class="lec-timegrid-gutter"></div>

        @foreach ($resources as $resource)
            @php
                $resourceId = (string) ($resource['id'] ?? '');
                $resourceTitle = (string) ($resource['title'] ?? '');
            @endphp

            <div
                class="lec-timegrid-day-header"
                data-testid="resource-timegrid-header-{{ $resourceId }}"
                data-resource-id="{{ $resourceId }}"
            >
                {{ $resourceTitle }}
            </div>
        @endforeach
    </div>

    <div class="lec-allday-row" data-testid="resource-timegrid-allday-row">
        <div class="lec-timegrid-gutter lec-allday-label">all-day</div>

        @foreach ($resources as $resource)
            @php
                $resourceId = (string) ($resource['id'] ?? '');
                $cellEvents = $resourceId !== '' ? ($alldayEventsByResourceId[$resourceId] ?? []) : [];
            @endphp

            <div
                class="lec-allday-cell"
                data-testid="resource-timegrid-allday-cell-{{ $resourceId }}"
                data-resource-id="{{ $resourceId }}"
                data-date="{{ $dateStr }}"
            >
                @foreach ($cellEvents as $event)
                    @php
                        $eventId = (string) ($event['id'] ?? '');
                        $bgCandidate = (string) ($event['backgroundColor'] ?? ($event['color'] ?? ''));
                        $bgCandidate = trim($bgCandidate);
                        $bgHex = preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $bgCandidate) === 1
                            ? $bgCandidate
                            : '';
                    @endphp

                    <div
                        class="lec-allday-event"
                        data-testid="resource-allday-event-{{ $eventId }}-{{ $resourceId }}-{{ $dateStr }}"
                        data-event-id="{{ $eventId }}"
                        data-resource-id="{{ $resourceId }}"
                        data-date="{{ $dateStr }}"
                        @if ($bgHex !== '')
                            style="--lec-event-bg: {{ $bgHex }};"
                        @endif
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
                            data-testid="resource-time-label-{{ $timeStr }}"
                        @endif
                    >
                        @if ($mins === 0)
                            {{ $timeStr }}
                        @endif
                    </div>

                    @foreach ($resources as $resource)
                        @php
                            $resourceId = (string) ($resource['id'] ?? '');
                        @endphp

                        <div
                            class="lec-slot-cell"
                            data-testid="resource-slot-cell-{{ $resourceId }}-{{ $dateStr }}-{{ $timeStr }}"
                            data-resource-id="{{ $resourceId }}"
                            data-date="{{ $dateStr }}"
                            data-minute="{{ $minutes }}"
                        ></div>
                    @endforeach
                </div>
            @endfor

            <div class="lec-timegrid-events-layer">
                @foreach ($resources as $resource)
                    @php
                        $resourceId = (string) ($resource['id'] ?? '');
                        $segments = $resourceId !== '' ? ($timedSegmentsByResourceId[$resourceId] ?? []) : [];
                    @endphp

                    <div
                        class="lec-timegrid-day-body"
                        data-testid="resource-timegrid-body-{{ $resourceId }}"
                        data-resource-id="{{ $resourceId }}"
                        data-date="{{ $dateStr }}"
                    >
                        @foreach ($segments as $seg)
                            @php
                                $event = is_array($seg['event'] ?? null) ? $seg['event'] : [];
                                $eventId = (string) ($event['id'] ?? '');
                                $isSelected = is_string($selectedEventId) && $selectedEventId !== '' && $selectedEventId === $eventId;
                                $startMin = (int) ($seg['startMin'] ?? 0);
                                $endMin = (int) ($seg['endMin'] ?? 0);
                                $bgCandidate = (string) ($event['backgroundColor'] ?? ($event['color'] ?? ''));
                                $bgCandidate = trim($bgCandidate);
                                $bgHex = preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $bgCandidate) === 1
                                    ? $bgCandidate
                                    : '';
                            @endphp

                            <div
                                class="lec-timed-event{{ $isSelected ? ' lec-event--selected' : '' }}"
                                data-testid="resource-timed-event-{{ $eventId }}-{{ $resourceId }}-{{ $dateStr }}"
                                data-event-id="{{ $eventId }}"
                                data-resource-id="{{ $resourceId }}"
                                data-date="{{ $dateStr }}"
                                data-start-min="{{ $startMin }}"
                                data-end-min="{{ $endMin }}"
                                data-col="0"
                                data-col-count="1"
                                data-event-start="{{ (string) ($event['start'] ?? '') }}"
                                data-event-end="{{ (string) ($event['end'] ?? '') }}"
                                style="--lec-start-min: {{ $startMin }}; --lec-end-min: {{ $endMin }};@if ($bgHex !== '') --lec-event-bg: {{ $bgHex }};@endif"
                                @if ($isSelected)
                                    data-selected="true"
                                @endif
                            >
                                <span class="lec-timed-event-title">{{ (string) ($event['title'] ?? '') }}</span>
                                <div
                                    class="lec-resize-handle"
                                    data-testid="resource-timed-event-resize-handle-{{ $eventId }}-{{ $resourceId }}-{{ $dateStr }}"
                                ></div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
