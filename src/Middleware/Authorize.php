<?php

declare(strict_types=1);

namespace LaravelHyperf\Auth\Middleware;

use Hyperf\Database\Model\Model;
use Hyperf\HttpServer\Router\Dispatched;
use LaravelHyperf\Auth\Access\AuthorizationException;
use LaravelHyperf\Auth\Contracts\Gate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Authorize implements MiddlewareInterface
{
    /**
     * Create a new middleware instance.
     *
     * @param Gate $gate the gate instance
     */
    public function __construct(protected Gate $gate)
    {
    }

    /**
     * Specify the ability and models for the middleware.
     */
    public static function using(string $ability, string ...$models): string
    {
        return static::class . ':' . implode(',', [$ability, ...$models]);
    }

    /**
     * Handle an incoming request.
     *
     * @throws AuthorizationException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler, ?string $ability = null, string ...$models): ResponseInterface
    {
        if (! is_null($ability)) {
            $this->gate->authorize($ability, $this->getGateArguments($request, $models));
        }

        return $handler->handle($request);
    }

    /**
     * Get the arguments parameter for the gate.
     */
    protected function getGateArguments(ServerRequestInterface $request, array $models): array|Model|string
    {
        if (is_null($models)) {
            return [];
        }

        return collect($models)->map(function ($model) use ($request) {
            return $model instanceof Model ? $model : $this->getModel($request, $model);
        })->all();
    }

    /**
     * Get the model to authorize.
     */
    protected function getModel(ServerRequestInterface $request, string $model): null|Model|string
    {
        if ($this->isClassName($model)) {
            return trim($model);
        }

        /** @var Dispatched */
        $dispatched = $request->getAttribute(Dispatched::class);

        if (! $dispatched->isFound()) {
            return null;
        }

        return $dispatched->params[$model] ?? null;
    }

    /**
     * Checks if the given string looks like a fully qualified class name.
     */
    protected function isClassName(string $value): bool
    {
        return str_contains($value, '\\');
    }
}
