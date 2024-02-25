<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Attribute\Context;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\Schema;
use ReflectionMethod;

class PathParameterDescriber implements OperationDescriberInterface
{
    public const IN_PATH_PARAMETERS_CONTEXT = 'inPathParameters';

    public function __construct(
        private PropertyDescriber $propertyDescriber,
        private ReflectionPreparer $reflectionPreparer,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, array $context = []): void
    {
        $this->addFromAttributes($operation, $reflectionMethod);

        foreach ($this->reflectionPreparer->getArgumentTypes($reflectionMethod) as $name => $types) {
            if (!array_key_exists($name, $context[self::IN_PATH_PARAMETERS_CONTEXT] ?? [])) {
                continue;
            }

            $schema = new Schema([]);

            /** @var Context $currentPropertyContext */
            $currentPropertyContext = $context[self::IN_PATH_PARAMETERS_CONTEXT][$name];

            $this->propertyDescriber->describe($schema, $currentPropertyContext->jsonSerialize(), ...$types);

            $parameter = Util::getOperationParameter($operation, $name, 'path');

            Util::merge($parameter, [
                'required' => true,
                'schema' => $schema,
            ], true);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addFromAttributes(Operation $operation, ReflectionMethod $reflectionMethod): void
    {
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Parameter && 'path' === $attributeInstance->in) {
                $newParameter = Util::getOperationParameter($operation, $attributeInstance->name, $attributeInstance->in);
                Util::merge($newParameter, $attributeInstance);
            }
        }
    }
}
