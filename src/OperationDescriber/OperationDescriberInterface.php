<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber;

use OpenApi\Annotations\Operation;
use ReflectionMethod;

interface OperationDescriberInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, array $context = []): void;
}
