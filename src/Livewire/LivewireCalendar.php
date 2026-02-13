<?php

namespace Calendar\LivewireCalendar\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class LivewireCalendar extends Component
{
    public function render(): View
    {
        return view('livewire-calendar::livewire-calendar');
    }
}

