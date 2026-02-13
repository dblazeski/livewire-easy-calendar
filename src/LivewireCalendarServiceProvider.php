<?php

namespace Calendar\LivewireCalendar;

use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Livewire\LivewireManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LivewireCalendarServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('livewire-calendar')
            ->hasViews()
            ->hasAssets();
    }

    public function packageBooted(): void
    {
        app(LivewireManager::class)->addComponent(
            name: 'livewire-calendar',
            class: LivewireCalendar::class,
        );
    }
}
