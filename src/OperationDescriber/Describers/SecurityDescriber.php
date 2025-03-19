<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Attribute\Security;
use Kr0lik\DtoToSwagger\Dto\RouteContextDto;
use Kr0lik\DtoToSwagger\Helper\AttributeHelper;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use OpenApi\Annotations\Operation;
use ReflectionException;
use ReflectionMethod;

class SecurityDescriber implements OperationDescriberInterface
{
    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, RouteContextDto $routeContext): void
    {
        $this->addFromAttributes($operation, $reflectionMethod);

        if ([] !== $routeContext->defaultSecurities) {
            Util::merge($operation, ['security' => $routeContext->defaultSecurities]);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function addFromAttributes(Operation $operation, ReflectionMethod $reflectionMethod): void
    {
        foreach (AttributeHelper::getAttributes($reflectionMethod) as $attribute) {
            $instance = $attribute->newInstance();

            if ($instance instanceof Security) {
                Util::merge($operation, ['security' => [$instance->name => $instance->scopes]], true);
            }
        }
    }
}
