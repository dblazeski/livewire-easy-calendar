@php
    $months = is_array($months ?? null) ? $months : [];
@endphp

<div class="lec-multimonth-view" data-testid="multimonth-view">
    @foreach ($months as $month)
        @php
            $monthKey = (string) ($month['key'] ?? '');
            $monthTitle = (string) ($month['title'] ?? '');
            $weekdayNames = is_array($month['weekdayNames'] ?? null) ? $month['weekdayNames'] : [];
            $cells = is_array($month['cells'] ?? null) ? $month['cells'] : [];
        @endphp

        <div class="lec-multimonth-month" data-testid="multimonth-month-{{ $monthKey }}">
            <div class="lec-multimonth-title">{{ $monthTitle }}</div>

            <div class="lec-multimonth-grid">
                @foreach ($weekdayNames as $name)
                    <div class="lec-multimonth-dow-header">{{ (string) $name }}</div>
                @endforeach

                @foreach ($cells as $cell)
                    @php
                        $dateStr = (string) ($cell['date'] ?? '');
                        $isCurrentMonth = ($cell['currentMonth'] ?? false) === true;
                    @endphp

                    <div
                        class="lec-multimonth-day{{ $isCurrentMonth ? '' : ' lec-multimonth-day--outside' }}"
                        data-date="{{ $dateStr }}"
                        data-current-month="{{ $isCurrentMonth ? 'true' : 'false' }}"
                    >
                        {{ (int) ($cell['day'] ?? 0) }}
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
