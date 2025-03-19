<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber;

use Kr0lik\DtoToSwagger\Dto\RouteContextDto;
use OpenApi\Annotations\Operation;
use ReflectionMethod;

interface OperationDescriberInterface
{
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, RouteContextDto $routeContext): void;
}
