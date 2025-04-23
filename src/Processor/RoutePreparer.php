<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Processor;

use Illuminate\Routing\Route;
use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Dto\RouteContextDto;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriber;
use Kr0lik\DtoToSwagger\Register\OpenApiRegister;
use OpenApi\Annotations\OpenApi;
use ReflectionException;
use ReflectionMethod;

class RoutePreparer
{
    private const SUPPORTED_METHODS = ['get', 'post', 'put', 'patch', 'delete'];

    public function __construct(
        private OpenApiRegister $openApiRegister,
        private OperationDescriber $operationDescriber,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function prepare(Route $route): void
    {
        $openapi = $this->openApiRegister->getOpenApi();

        assert($openapi instanceof OpenApi);

        $pathItem = Util::getPath($openapi, $this->getPath($route));

        foreach ($route->methods() as $method) {
            if (! $this->isSupported($method)) {
                continue;
            }

            $context = new RouteContextDto(
                inPathParametersPerName: $this->getParametersPerName($route),
            );

            $operation = Util::getOperation($pathItem, $method);

            $defaultSecurities = $this->getSecurities($route);

            if ($defaultSecurities !== []) {
                Util::merge($operation, ['security' => $defaultSecurities]);
            }

            $defaultTags = $this->getTags($route);

            if ($defaultTags !== []) {
                Util::merge($operation, ['tags' => $defaultTags]);
            }

            $defaultErrorResponseSchemas = $this->openApiRegister->getConfig()->defaultErrorResponseSchemas ?? [];

            if ($defaultErrorResponseSchemas !== []) {
                Util::merge($operation, ['responses' => $defaultErrorResponseSchemas]);
            }

            $reflectionMethod = $this->getReflection($route);

            if ($reflectionMethod === null) {
                continue;
            }

            $this->operationDescriber->describe($operation, $reflectionMethod, $context);
        }
    }

    public function getPath(Route $route): string
    {
        return '/'.ltrim($route->uri, '/');
    }

    private function isSupported(string $method): bool
    {
        return in_array(strtolower($method), self::SUPPORTED_METHODS, true);
    }

    /**
     * @throws ReflectionException
     */
    private function getReflection(Route $route): ?ReflectionMethod
    {
        if (! is_string($route->action['uses']) || ! str_contains($route->action['uses'], '@')) {
            return null;
        }

        [$class, $action] = explode('@', $route->action['uses']);

        return new ReflectionMethod($class, $action);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getParametersPerName(Route $route): array
    {
        /** @var string[] $routeParameterNames */
        $routeParameterNames = $route->parameterNames();

        $parameterNames = [];

        foreach ($routeParameterNames as $parameterName) {
            $pattern = $route->wheres[$parameterName] ?? null;

            $parameterNames[$parameterName] = ['pattern' => $pattern];
        }

        return $parameterNames;
    }

    /**
     * @return array<array<string, array<mixed>>>
     */
    private function getSecurities(Route $route): array
    {
        $middlewaresToAuth = $this->openApiRegister->getConfig()->middlewaresToAuth ?? [];

        if ($middlewaresToAuth === []) {
            return [];
        }

        $routeMiddlewares = $route->middleware();

        if (! is_array($routeMiddlewares)) {
            return [];
        }

        $securities = [];

        foreach ($routeMiddlewares as $middleware) {
            if (array_key_exists($middleware, $middlewaresToAuth)) {
                $securities[] = $middlewaresToAuth[$middleware];
            }
        }

        return $securities;
    }

    /**
     * @return string[]
     */
    private function getTags(Route $route): array
    {
        $tagFromMiddlewares = $this->openApiRegister->getConfig()->tagFromMiddlewares ?? [];

        if ($tagFromMiddlewares === []) {
            return [];
        }

        $routeMiddlewares = $route->middleware();

        if (! is_array($routeMiddlewares)) {
            return [];
        }

        $tags = [];

        foreach ($routeMiddlewares as $middleware) {
            if (in_array($middleware, $tagFromMiddlewares, true)) {
                $tags[] = $middleware;
            }
        }

        return $tags;
    }
}
