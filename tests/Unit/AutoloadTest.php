<?php

it('autoloads the package service provider class', function (): void {
    expect(class_exists(\Calendar\LivewireCalendar\LivewireCalendarServiceProvider::class))->toBeTrue();
});
