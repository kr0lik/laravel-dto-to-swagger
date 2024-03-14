<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Contract\HeaderRequestInterface;
use Kr0lik\DtoToSwagger\Helper\ContextHelper;
use Kr0lik\DtoToSwagger\Helper\NameHelper;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\Helper\ClassHelper;
use Kr0lik\DtoToSwagger\ReflectionPreparer\PhpDocReader;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class HeaderParameterDescriber implements OperationDescriberInterface
{
    private const IN = 'header';

    public function __construct(
        private ReflectionPreparer $reflectionPreparer,
        private PropertyTypeDescriber $propertyDescriber,
        private PhpDocReader $phpDocReader,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, array $context = []): void
    {
        $this->addFromAttributes($operation, $reflectionMethod);

        foreach ($this->reflectionPreparer->getArgumentTypes($reflectionMethod) as $types) {
            if (1 === count($types) && is_subclass_of($types[0]->getClassName(), HeaderRequestInterface::class)) {
                $reflectionClass = new ReflectionClass($types[0]->getClassName());

                $this->addHeaderParametersFromObject($operation, $reflectionClass);
            }
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

    /**
     * @throws InvalidArgumentException
     */
    private function addHeaderParametersFromObject(Operation $operation, ReflectionClass $reflectionClass): void
    {
        foreach (ClassHelper::getVisibleProperties($reflectionClass) as $reflectionProperty) {
            $parameter = $this->getParameter($operation, $reflectionProperty);

            Util::merge($parameter, [
                'required' => $this->isRequired($reflectionProperty),
                'schema' => $this->getSchema($reflectionProperty),
            ], true);

            if ($this->isDeprecated($reflectionProperty)) {
                $parameter->deprecated = true;
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getParameter(Operation $operation, ReflectionProperty $reflectionProperty): Parameter
    {
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Parameter) {
                $name = $attributeInstance->name;

                if (Generator::UNDEFINED === $name || null === $name || '' === $name) {
                    $name = NameHelper::getName($reflectionProperty);
                }

                $in = $attributeInstance->in;

                if (Generator::UNDEFINED === $in || null === $in || '' === $in) {
                    $in = self::IN;
                }

                return Util::getOperationParameter($operation, $name, $in);
            }
        }

        return Util::getOperationParameter($operation, NameHelper::getName($reflectionProperty), self::IN);
    }

    private function getSchema(ReflectionProperty $reflectionProperty): Schema
    {
        $schema = new Schema([]);

        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Schema) {
                $schema = $attributeInstance;
            }
        }

        $types = $this->reflectionPreparer->getTypes($reflectionProperty);

        $context = ContextHelper::getContext($reflectionProperty);

        $this->propertyDescriber->describe($schema, $context, ...$types);

        return $schema;
    }

    private function isDeprecated(ReflectionProperty $reflectionProperty): bool
    {
        if ($this->phpDocReader->isDeprecated($reflectionProperty)) {
            return true;
        }

        return false;
    }

    private function isRequired(ReflectionProperty $reflectionProperty): bool
    {
        if ($reflectionProperty->hasDefaultValue()) {
            return false;
        }

        foreach ($reflectionProperty->getDeclaringClass()->getConstructor()?->getParameters() ?? [] as $constructorParameter) {
            if ($constructorParameter->getName() === $reflectionProperty->getName() && $constructorParameter->isDefaultValueAvailable()) {
                return false;
            }
        }

        return true;
    }
}
