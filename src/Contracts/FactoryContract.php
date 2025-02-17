<?php

declare(strict_types=1);

namespace LaravelHyperf\Auth\Contracts;

interface FactoryContract
{
    /**
     * Get a guard instance by name.
     */
    public function guard(?string $name = null): Guard|StatefulGuard;

    /**
     * Set the default guard the factory should serve.
     */
    public function shouldUse(string $name): void;
}
