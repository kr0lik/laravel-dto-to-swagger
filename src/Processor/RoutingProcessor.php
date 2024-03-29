<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Processor;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as LaravelRoute;
use InvalidArgumentException;
use OpenApi\Annotations\OpenApi;
use ReflectionException;
use RuntimeException;

class RoutingProcessor
{
    /**
     * @param string[] $includeMiddlewares
     * @param string[] $includePatterns
     * @param string[] $excludeMiddlewares
     * @param string[] $excludePatterns
     */
    public function __construct(
        private RouteProcessor $routeProcessor,
        private array $includeMiddlewares,
        private array $includePatterns,
        private array $excludeMiddlewares,
        private array $excludePatterns,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function process(OpenApi $openApi): void
    {
        foreach ($this->fetchRoute() as $route) {
            $this->routeProcessor->process($openApi, $route);
        }
    }

    /**
     * @throws RuntimeException
     *
     * @return iterable<Route>
     */
    private function fetchRoute(): iterable
    {
        $laravelRouteCollection = LaravelRoute::getRoutes();

        foreach ($laravelRouteCollection->getRoutes() as $route) {
            if (!$this->isMatch($route)) {
                continue;
            }

            yield $route;
        }
    }

    private function isMatch(Route $route): bool
    {
        return $this->isMatchMiddleware($route)
            && $this->isMatchPattern($route)
            && $this->isNotMatchExcludeMiddleware($route)
            && $this->isNotMatchExcludePattern($route);
    }

    private function isMatchMiddleware(Route $route): bool
    {
        if ([] !== $this->includeMiddlewares) {
            return [] !== array_intersect($this->includeMiddlewares, (array) $route->middleware());
        }

        return true;
    }

    private function isMatchPattern(Route $route): bool
    {
        if ([] === $this->includePatterns) {
            return true;
        }

        foreach ($this->includePatterns as $pathPattern) {
            if (0 !== preg_match('/'.$pathPattern.'/', $route->uri())) {
                return true;
            }
        }

        return false;
    }

    private function isNotMatchExcludeMiddleware(Route $route): bool
    {
        if ([] !== $this->excludeMiddlewares) {
            return [] === array_intersect($this->includeMiddlewares, (array) $route->middleware());
        }

        return true;
    }

    private function isNotMatchExcludePattern(Route $route): bool
    {
        if ([] === $this->excludePatterns) {
            return true;
        }

        foreach ($this->excludePatterns as $pathPattern) {
            if (0 !== preg_match('/'.$pathPattern.'/', $route->uri())) {
                return false;
            }
        }

        return true;
    }
}
