<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Workbench\App\Livewire\Docs\DemoCalendar;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->configureDatabase();
    }

    public function boot(): void
    {
        Livewire::component('docs-demo-calendar', DemoCalendar::class);
    }

    private function configureDatabase(): void
    {
        $databasePath = $this->demoDatabasePath();

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', $databasePath);
        $this->app['config']->set('cache.default', 'array');
    }

    private function demoDatabasePath(): string
    {
        $workbenchPath = dirname(__DIR__, 2);

        return $workbenchPath.'/database/demo.sqlite';
    }
}
