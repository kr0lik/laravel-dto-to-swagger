<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Processor;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as LaravelRoute;
use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Register\OpenApiRegister;
use ReflectionException;
use RuntimeException;

class RoutingProcessor extends AbstractProcessor
{
    public function __construct(
        private RoutePreparer $routePrepaprer,
        OpenApiRegister $openApiRegister,
    ) {
        parent::__construct($openApiRegister);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function prepare(): void
    {
        foreach ($this->fetchRoute() as $route) {
            $this->routePrepaprer->prepare($route);
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
        $includeMiddlewares = $this->openApiRegister->getConfig()->includeMiddlewares ?? [];

        if ([] !== $includeMiddlewares && is_array($route->middleware())) {
            return [] !== array_intersect($includeMiddlewares, $route->middleware());
        }

        return true;
    }

    private function isMatchPattern(Route $route): bool
    {
        $includePatterns = $this->openApiRegister->getConfig()->includePatterns ?? [];

        if ([] === $includePatterns) {
            return true;
        }

        foreach ($includePatterns as $pathPattern) {
            if (0 !== preg_match('/'.$pathPattern.'/', $route->uri())) {
                return true;
            }
        }

        return false;
    }

    private function isNotMatchExcludeMiddleware(Route $route): bool
    {
        $excludeMiddlewares = $this->openApiRegister->getConfig()->excludeMiddlewares ?? [];

        if ([] !== $excludeMiddlewares && is_array($route->middleware())) {
            return [] === array_intersect($excludeMiddlewares, $route->middleware());
        }

        return true;
    }

    private function isNotMatchExcludePattern(Route $route): bool
    {
        $excludePatterns = $this->openApiRegister->getConfig()->excludePatterns ?? [];

        if ([] === $excludePatterns) {
            return true;
        }

        foreach ($excludePatterns as $pathPattern) {
            if (0 !== preg_match('/'.$pathPattern.'/', $route->uri())) {
                return false;
            }
        }

        return true;
    }
}
