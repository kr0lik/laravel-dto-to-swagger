<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Processor;

use Illuminate\Routing\Route;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\Describers\PathParameterDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\Describers\SecurityDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriber;
use OpenApi\Annotations\OpenApi;
use ReflectionException;
use ReflectionMethod;

class RouteProcessor
{
    private const SUPPORTED_METHODS = ['get', 'post', 'put', 'patch', 'delete'];

    /**
     * @param array<string, array<string, array<mixed>>> $middlewaresToAuth
     */
    public function __construct(
        private OperationDescriber $operationDescriber,
        private array $middlewaresToAuth,
    ) {}

    /**
     * @throws ReflectionException
     */
    public function process(OpenApi $openApi, Route $route): void
    {
        $pathItem = Util::getPath($openApi, $this->getPath($route));

        foreach ($route->methods() as $method) {
            if (!$this->isSupported($method)) {
                continue;
            }

            $context = [];
            $context[PathParameterDescriber::IN_PATH_PARAMETERS_CONTEXT] = (array) $route->parameterNames();
            $context[SecurityDescriber::DEFAULT_SECURITIES_CONTEXT] = $this->getSecurities($route);

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
     * @return array<array<string, array<mixed>>>
     */
    private function getSecurities(Route $route): array
    {
        $securities = [];

        foreach ((array) $route->middleware() as $middleware) {
            if (array_key_exists($middleware, $this->middlewaresToAuth)) {
                $securities[] = $this->middlewaresToAuth[$middleware];
            }
        }

        return $securities;
    }
}
