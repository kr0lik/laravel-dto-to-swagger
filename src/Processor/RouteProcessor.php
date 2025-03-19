<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Processor;

use Illuminate\Routing\Route;
use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Dto\RouteContextDto;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriber;
use Kr0lik\DtoToSwagger\Register\OpenApiRegister;
use ReflectionException;
use ReflectionMethod;

class RouteProcessor
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
    public function process(Route $route): void
    {
        $pathItem = Util::getPath($this->openApiRegister->getOpenApi(), $this->getPath($route));

        foreach ($route->methods() as $method) {
            if (!$this->isSupported($method)) {
                continue;
            }

            $context = new RouteContextDto(
                inPathParametersPerName: $this->getParametersPerName($route),
                defaultSecurities: $this->getSecurities($route),
                defaultTags: $this->getTags($route)
            );

            $operation = Util::getOperation($pathItem, $method);

            $this->operationDescriber->describe($operation, $this->getReflection($route), $context);
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
    private function getReflection(Route $route): ReflectionMethod
    {
        [$class, $action] = explode('@', $route->action['uses']);

        return new ReflectionMethod($class, $action);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getParametersPerName(Route $route): array
    {
        $parameterNames = [];

        /** @var string $parameterName */
        foreach ($route->parameterNames() as $parameterName) {
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
        $securities = [];

        foreach ((array) $route->middleware() as $middleware) {
            if (array_key_exists($middleware, $this->openApiRegister->getConfig()->middlewaresToAuth)) {
                $securities[] = $this->openApiRegister->getConfig()->middlewaresToAuth[$middleware];
            }
        }

        return $securities;
    }

    /**
     * @return string[]
     */
    private function getTags(Route $route): array
    {
        $tags = [];

        foreach ((array) $route->middleware() as $middleware) {
            if (in_array($middleware, $this->openApiRegister->getConfig()->tagFromMiddlewares, true)) {
                $tags[] = $middleware;
            }
        }

        return $tags;
    }
}
