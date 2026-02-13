<?php

namespace Calendar\LivewireCalendar\Tests;

use Illuminate\Support\Facades\Facade;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Facade::setFacadeApplication($this->app);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            \Calendar\LivewireCalendar\LivewireCalendarServiceProvider::class,
        ];
    }
}
