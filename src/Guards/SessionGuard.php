<?php

declare(strict_types=1);

namespace LaravelHyperf\Auth\Guards;

use Hyperf\Context\Context;
use Hyperf\Macroable\Macroable;
use LaravelHyperf\Auth\Contracts\Authenticatable;
use LaravelHyperf\Auth\Contracts\StatefulGuard;
use LaravelHyperf\Auth\Contracts\UserProvider;
use LaravelHyperf\Session\Contracts\Session as SessionContract;
use Throwable;

class SessionGuard implements StatefulGuard
{
    use GuardHelpers;
    use Macroable;

    public function __construct(
        protected string $name,
        protected UserProvider $provider,
        protected SessionContract $session
    ) {
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     */
    public function attempt(array $credentials = [], bool $login = true): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        // If an implementation of UserInterface was returned, we'll ask the provider
        // to validate the user against the given credentials, and if they are in
        // fact valid we'll log the users into the application and return true.
        $result = $this->hasValidCredentials($user, $credentials);
        if ($result && $login) {
            $this->login($user);
        }

        return $result;
    }

    /**
     * Log a user into the application without sessions or cookies.
     */
    public function once(array $credentials = []): bool
    {
        if ($this->attempt($credentials)) {
            $this->setUser($this->user());

            return true;
        }

        return false;
    }

    public function login(Authenticatable $user): void
    {
        $this->session->put($this->sessionKey(), $user->getAuthIdentifier());

        $this->setUser($user);
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     */
    public function onceUsingId(mixed $id): Authenticatable|bool
    {
        if (! is_null($user = $this->provider->retrieveById($id))) {
            $this->setUser($user);

            return $user;
        }

        return false;
    }

    public function getContextKey(): string
    {
        return "auth.guards.{$this->name}.result:" . $this->session->getId();
    }

    public function user(): ?Authenticatable
    {
        // cache user in context
        if ($user = Context::get($contextKey = $this->getContextKey())) {
            return $user;
        }

        try {
            if ($id = $this->session->get($this->sessionKey())) {
                $user = $this->provider->retrieveById($id);
                Context::set($contextKey, $user ?: null);
            }
        } catch (Throwable $exception) {
            Context::set($contextKey, null);
        }

        return $user;
    }

    public function logout(): void
    {
        Context::set($this->getContextKey(), null);
        $this->session->remove($this->sessionKey());
    }

    public function setUser(Authenticatable $user): void
    {
        Context::set($this->getContextKey(), $user);
    }

    protected function sessionKey(): string
    {
        return 'auth_' . $this->name;
    }
}
