<?php

namespace Calendar\LivewireCalendar\Tests;

use Calendar\LivewireCalendar\LivewireCalendarServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireCalendarServiceProvider::class,
        ];
    }
}

