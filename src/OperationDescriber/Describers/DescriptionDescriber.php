<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use Kr0lik\DtoToSwagger\Dto\RouteContextDto;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\ReflectionPreparer\PhpDocReader;
use OpenApi\Annotations\Operation;
use ReflectionMethod;

class DescriptionDescriber implements OperationDescriberInterface
{
    public function __construct(
        private PhpDocReader $phpDocReader,
    ) {}

    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, RouteContextDto $routeContext): void
    {
        $description = $this->phpDocReader->getDescription($reflectionMethod);

        if ('' !== $description) {
            $operation->description = $description;
        }

        if ($this->phpDocReader->isDeprecated($reflectionMethod)) {
            $operation->deprecated = true;
        }
    }
}
