<?php

namespace Calendar\LivewireCalendar\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class DocsTestCase extends OrchestraTestCase
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        Facade::setFacadeApplication($this->app);

        $this->publishPackageAssets();
        $this->publishDocsAssets();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    private function publishPackageAssets(): void
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

    private function publishDocsAssets(): void
    {
        $source = dirname(__DIR__).'/workbench/public/docs-assets';
        $destination = public_path('docs-assets');

        if (! is_dir($source)) {
            return;
        }

        $fs = new Filesystem;
        $fs->ensureDirectoryExists($destination);
        $fs->copyDirectory($source, $destination);
    }
}
