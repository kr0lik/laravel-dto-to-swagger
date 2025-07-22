<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Dto\RouteContextDto;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use Kr0lik\DtoToSwagger\Trait\IsRequiredTrait;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use ReflectionMethod;

class PathParameterDescriber implements OperationDescriberInterface
{
    use IsRequiredTrait;

    public const IN = 'path';

    public function __construct(
        private PropertyTypeDescriber $propertyDescriber,
        private ReflectionPreparer $reflectionPreparer,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, RouteContextDto $routeContext): void
    {
        $this->addFromAttributes($operation, $reflectionMethod);

        foreach ($this->reflectionPreparer->getArgumentTypes($reflectionMethod) as $name => $types) {
            if (!array_key_exists($name, $routeContext->inPathParametersPerName)) {
                continue;
            }

            $parameter = Util::getOperationParameter($operation, $name, self::IN);
            $parameter->required = true;

            if (null !== $parameter->schema && Generator::UNDEFINED !== $parameter->schema) {
                continue;
            }

            $schema = new Schema([]);

            $currentPropertyContext = $routeContext->inPathParametersPerName[$name];

            $this->propertyDescriber->describe($schema, $currentPropertyContext, ...$types);

            $parameter->schema = $schema;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addFromAttributes(Operation $operation, ReflectionMethod $reflectionMethod): void
    {
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Parameter && self::IN === $attributeInstance->in) {
                $newParameter = Util::getOperationParameter($operation, $attributeInstance->name, $attributeInstance->in);
                Util::merge($newParameter, $attributeInstance);
            }
        }
    }
}
