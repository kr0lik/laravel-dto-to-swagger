<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber;

use Kr0lik\DtoToSwagger\Helper\Util;
use OpenApi\Annotations\Operation;
use ReflectionMethod;

final class OperationDescriber
{
    /**
     * @var OperationDescriberInterface[]
     */
    private array $operationDescribers;

    public function addOperationDescriber(OperationDescriberInterface $operationDescriber): void
    {
        $this->operationDescribers[] = $operationDescriber;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, array $context = []): void
    {
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $instanceAttribute = $attribute->newInstance();

            if ($instanceAttribute instanceof Operation) {
                Util::merge($operation, $instanceAttribute);
            }
        }

        foreach ($this->operationDescribers as $describer) {
            $describer->describe($operation, $reflectionMethod, $context);
        }
    }
}
