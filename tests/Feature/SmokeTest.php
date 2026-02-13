<?php

it('boots the testbench application', function (): void {
    expect(app())->toBeInstanceOf(\Illuminate\Contracts\Foundation\Application::class);
    expect(app()->bound('livewire.finder'))->toBeTrue();
});

