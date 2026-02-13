<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/TestCase.php';

// Apply the Testbench TestCase only to Feature tests (browser/component/integration).
pest()->extend(\Calendar\LivewireCalendar\Tests\TestCase::class)->in('Feature');
