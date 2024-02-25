<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Attribute\Security;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use OpenApi\Annotations\Operation;
use ReflectionMethod;

class SecurityDescriber implements OperationDescriberInterface
{
    public const DEFAULT_SECURITIES_CONTEXT = 'default_securities';

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, array $context = []): void
    {
        $this->addFromAttributes($operation, $reflectionMethod);

        if (
            array_key_exists(self::DEFAULT_SECURITIES_CONTEXT, $context)
            && is_array($context[self::DEFAULT_SECURITIES_CONTEXT])
            && [] !== $context[self::DEFAULT_SECURITIES_CONTEXT]
        ) {
            Util::merge($operation, ['security' => $context[self::DEFAULT_SECURITIES_CONTEXT]]);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addFromAttributes(Operation $operation, ReflectionMethod $reflectionMethod): void
    {
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();

            if ($instance instanceof Security) {
                Util::merge($operation, ['security' => [$instance->name => $instance->scopes]], true);
            }
        }
    }
}
