<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Processor;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as LaravelRoute;
use OpenApi\Annotations\OpenApi;
use ReflectionException;
use RuntimeException;

class RoutingProcessor
{
    /**
     * @param string[] $includeMiddlewares
     * @param string[] $includePatterns
     */
    public function __construct(
        private RouteProcessor $routeProcessor,
        private array $includeMiddlewares,
        private array $includePatterns,
    ) {}

    /**
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
            && $this->isMatchPattern($route);
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
        foreach ($this->includePatterns as $pathPattern) {
            if (false !== preg_match('{'.$pathPattern.'}', $route->uri())) {
                return true;
            }
        }

        return [] === $this->includePatterns;
    }
}
