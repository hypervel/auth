<?php

declare(strict_types=1);

namespace LaravelHyperf\Auth\Guards;

use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Macroable\Macroable;
use LaravelHyperf\Auth\Contracts\Authenticatable;
use LaravelHyperf\Auth\Contracts\Guard;
use LaravelHyperf\Auth\Contracts\UserProvider;
use Throwable;

class RequestGuard implements Guard
{
    use GuardHelpers;
    use Macroable;

    /**
     * The request instance.
     */
    protected RequestInterface $request;

    /**
     * The callback that should be used to authenticate users.
     */
    protected $callback;

    public function __construct(
        protected UserProvider $provider,
        callable $callback
    ) {
        $this->callback = $callback;
        $this->request = ApplicationContext::getContainer()
            ->get(RequestInterface::class);
    }

    public function user(): ?Authenticatable
    {
        // cache user in context
        if (Context::has($contextKey = $this->getContextKey())) {
            return Context::get($contextKey);
        }

        try {
            $user = call_user_func($this->callback, $this->getProvider());
            Context::set($contextKey, $user ?? null);
        } catch (Throwable $exception) {
            Context::set($contextKey, null);
        }

        return $user;
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        return ! is_null($this->user());
    }

    public function setUser(Authenticatable $user): void
    {
        Context::set($this->getContextKey(), $user);
    }

    protected function getContextKey(): string
    {
        return 'auth.guards.request';
    }
}
