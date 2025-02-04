<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Attribute\Nested;
use Kr0lik\DtoToSwagger\Contract\QueryRequestInterface;
use Kr0lik\DtoToSwagger\Helper\ContextHelper;
use Kr0lik\DtoToSwagger\Helper\NameHelper;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\Helper\ClassHelper;
use Kr0lik\DtoToSwagger\ReflectionPreparer\PhpDocReader;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use Kr0lik\DtoToSwagger\Trait\IsRequiredTrait;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

class QueryParameterDescriber implements OperationDescriberInterface
{
    use IsRequiredTrait;

    public const IN = 'query';

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
            if (1 === count($types) && is_subclass_of($types[0]->getClassName(), QueryRequestInterface::class)) {
                $reflectionClass = new ReflectionClass($types[0]->getClassName());

                $this->addQueryParametersFromObject($operation, $reflectionClass);
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
     * @throws ReflectionException
     */
    private function addQueryParametersFromObject(Operation $operation, ReflectionClass $reflectionClass): void
    {
        foreach (ClassHelper::getVisiblePropertiesRecursively($reflectionClass) as $reflectionProperty) {
            if ($this->isNested($reflectionProperty)) {
                $this->addNestedParameters($operation, $reflectionProperty);

                continue;
            }

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
     * @param string[] $nestedNames
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function addNestedParameters(Operation $operation, ReflectionProperty $nestedReflectionProperty, array $nestedNames = []): void
    {
        $nestedNames[] = $this->getName($nestedReflectionProperty);

        /** @phpstan-ignore-next-line */
        $nestedReflectionClassName = $nestedReflectionProperty->getType()->getName();
        $nestedReflectionClass = new ReflectionClass($nestedReflectionClassName);

        foreach (ClassHelper::getVisiblePropertiesRecursively($nestedReflectionClass) as $reflectionProperty) {
            if ($this->isNested($reflectionProperty)) {
                $this->addNestedParameters($operation, $reflectionProperty, $nestedNames);

                continue;
            }

            $parameter = $this->getParameter($operation, $reflectionProperty);

            Util::merge($parameter, [
                'required' => $this->isRequired($reflectionProperty),
                'schema' => $this->getSchema($reflectionProperty),
                'name' => $this->buildNestedName(array_merge($nestedNames, [$parameter->name])),
            ], true);

            if ($this->isDeprecated($reflectionProperty)) {
                $parameter->deprecated = true;
            }

            if ($this->isDeprecated($nestedReflectionProperty)) {
                $parameter->deprecated = true;
            }
        }
    }

    private function isNested(ReflectionProperty $reflectionProperty): bool
    {
        if (!$reflectionProperty->getType() instanceof ReflectionNamedType || $reflectionProperty->getType()->isBuiltin()) {
            return false;
        }

        $attributes = $reflectionProperty->getAttributes(Nested::class);

        foreach ($attributes as $attribute) {
            if (Nested::class === $attribute->getName()) {
                return true;
            }
        }

        return false;
    }

    private function getName(ReflectionProperty $reflectionProperty): string
    {
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Parameter) {
                $name = $attributeInstance->name;

                if (Generator::UNDEFINED !== $name && null !== $name && '' !== $name) {
                    return $name;
                }
            }
        }

        return NameHelper::getName($reflectionProperty);
    }

    /**
     * @param string[] $nestedNames
     */
    private function buildNestedName(array $nestedNames): string
    {
        $result = array_shift($nestedNames);

        foreach ($nestedNames as $name) {
            $result .= "[{$name}]";
        }

        return $result;
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

                if (Generator::UNDEFINED === $attributeInstance->in || null === $attributeInstance->in || '' === $attributeInstance->in) {
                    $attributeInstance->in = self::IN;
                }

                $newParameter = Util::getOperationParameter($operation, $name, $attributeInstance->in);
                Util::merge($newParameter, $attributeInstance);

                return $newParameter;
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
}
