<?php

it('boots the testbench application', function (): void {
    expect(app())->toBeInstanceOf(\Illuminate\Contracts\Container\Container::class);
});
