@php
    $dateStr = (string) ($date ?? '');
    $resources = is_array($resources ?? null) ? $resources : [];
    $eventsByResource = is_array($eventsByResource ?? null) ? $eventsByResource : [];
@endphp

<div class="lec-resource-timeline" data-testid="resource-timeline" data-date="{{ $dateStr }}">
    <div class="lec-resource-timeline-header">
        <div class="lec-resource-timeline-header-left">Resources</div>

        <div class="lec-resource-timeline-axis">
            @for ($minutes = 0; $minutes < 1440; $minutes += 30)
                @php
                    $hours = intdiv($minutes, 60);
                    $mins = $minutes % 60;
                    $timeStr = str_pad((string) $hours, 2, '0', STR_PAD_LEFT).':'.str_pad((string) $mins, 2, '0', STR_PAD_LEFT);
                @endphp

                <div
                    class="lec-resource-timeline-axis-tick"
                    data-testid="resource-axis-tick-{{ $timeStr }}"
                    data-minute="{{ $minutes }}"
                >
                    @if ($mins === 0)
                        {{ $timeStr }}
                    @endif
                </div>
            @endfor
        </div>
    </div>

    <div class="lec-resource-timeline-body">
        @foreach ($resources as $resource)
            @php
                $resourceId = (string) ($resource['id'] ?? '');
                $resourceTitle = (string) ($resource['title'] ?? '');
                $segments = $resourceId !== '' ? ($eventsByResource[$resourceId] ?? []) : [];
            @endphp

            <div class="lec-resource-timeline-row" data-testid="resource-row-{{ $resourceId }}" data-resource-id="{{ $resourceId }}">
                <div class="lec-resource-timeline-label" data-testid="resource-label-{{ $resourceId }}">{{ $resourceTitle }}</div>

                <div class="lec-resource-timeline-lane" data-resource-id="{{ $resourceId }}">
                    @foreach ($segments as $seg)
                        @php
                            $event = is_array($seg['event'] ?? null) ? $seg['event'] : [];
                            $eventId = (string) ($event['id'] ?? '');
                            $startMin = (int) ($seg['startMin'] ?? 0);
                            $endMin = (int) ($seg['endMin'] ?? 0);
                            $allDay = ($event['allDay'] ?? false) === true;
                        @endphp

                        <div
                            class="lec-resource-timeline-event"
                            data-testid="resource-event-{{ $eventId }}-{{ $resourceId }}"
                            data-resource-id="{{ $resourceId }}"
                            data-start-min="{{ $startMin }}"
                            data-end-min="{{ $endMin }}"
                            data-event-id="{{ $eventId }}"
                            data-event-start="{{ (string) ($event['start'] ?? '') }}"
                            data-event-end="{{ (string) ($event['end'] ?? '') }}"
                            style="--lec-start-min: {{ $startMin }}; --lec-end-min: {{ $endMin }}"
                            @if ($allDay)
                                data-all-day="true"
                            @endif
                        >
                            {{ (string) ($event['title'] ?? '') }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
