<?php

namespace Calendar\LivewireCalendar\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Facade::setFacadeApplication($this->app);

        $this->publishAssetsForBrowserTests();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            \Calendar\LivewireCalendar\LivewireCalendarServiceProvider::class,
        ];
    }

    private function publishAssetsForBrowserTests(): void
    {
        $source = dirname(__DIR__).'/resources/dist';
        $destination = public_path('vendor/livewire-calendar');

        if (! is_dir($source)) {
            return;
        }

        $fs = new Filesystem;
        $fs->ensureDirectoryExists($destination);
        $fs->copyDirectory($source, $destination);
    }
}
