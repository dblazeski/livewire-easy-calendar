@php
    $view = (string) ($view ?? 'month');
@endphp

<div class="lec-toolbar" data-testid="calendar-toolbar">
    <div class="lec-toolbar-nav" data-testid="calendar-toolbar-nav">
        @include($this->componentView('header-nav-prev'))
        @include($this->componentView('header-nav-today'))
        @include($this->componentView('header-nav-next'))
    </div>

    <div
        class="lec-view-switcher"
        data-testid="view-switcher"
        role="group"
        aria-label="Calendar view"
    >
        @include($this->componentView('header-view-month'), ['view' => $view])
        @include($this->componentView('header-view-timeGridWeek'), ['view' => $view])
        @include($this->componentView('header-view-timeGridDay'), ['view' => $view])
        @include($this->componentView('header-view-listWeek'), ['view' => $view])
        @include($this->componentView('header-view-multiMonthYear'), ['view' => $view])
        @include($this->componentView('header-view-resourceTimelineDay'), ['view' => $view])
        @include($this->componentView('header-view-resourceTimeGridDay'), ['view' => $view])
    </div>
</div>
