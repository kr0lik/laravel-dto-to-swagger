<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Processor;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as LaravelRoute;
use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Register\OpenApiRegister;
use ReflectionException;
use RuntimeException;

class RoutingProcessor
{
    public function __construct(
        private OpenApiRegister $openApiRegister,
        private RouteProcessor $routeProcessor,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function process(): void
    {
        foreach ($this->fetchRoute() as $route) {
            $this->routeProcessor->process($route);
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
        if ([] !== $this->openApiRegister->getConfig()->includeMiddlewares) {
            return [] !== array_intersect($this->openApiRegister->getConfig()->includeMiddlewares, (array) $route->middleware());
        }

        return true;
    }

    private function isMatchPattern(Route $route): bool
    {
        if ([] === $this->openApiRegister->getConfig()->includePatterns) {
            return true;
        }

        foreach ($this->openApiRegister->getConfig()->includePatterns as $pathPattern) {
            if (0 !== preg_match('/'.$pathPattern.'/', $route->uri())) {
                return true;
            }
        }

        return false;
    }

    private function isNotMatchExcludeMiddleware(Route $route): bool
    {
        if ([] !== $this->openApiRegister->getConfig()->excludeMiddlewares) {
            return [] === array_intersect($this->openApiRegister->getConfig()->includeMiddlewares, (array) $route->middleware());
        }

        return true;
    }

    private function isNotMatchExcludePattern(Route $route): bool
    {
        if ([] === $this->openApiRegister->getConfig()->excludePatterns) {
            return true;
        }

        foreach ($this->openApiRegister->getConfig()->excludePatterns as $pathPattern) {
            if (0 !== preg_match('/'.$pathPattern.'/', $route->uri())) {
                return false;
            }
        }

        return true;
    }
}
