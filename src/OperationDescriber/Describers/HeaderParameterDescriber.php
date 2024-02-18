<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use Kr0lik\DtoToSwagger\Contract\HeaderRequestInterface;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\PhpDocReader;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\Schema;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class HeaderParameterDescriber implements OperationDescriberInterface
{
    public function __construct(
        private ReflectionPreparer $reflectionPreparer,
        private PropertyInfoExtractor $propertyInfoExtractor,
        private PropertyDescriber $propertyDescriber,
        private PhpDocReader $phpDocReader,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws ReflectionException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, array $context = []): void
    {
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Parameter && 'header' === $attributeInstance->in) {
                $newParameter = Util::getOperationParameter($operation, $attributeInstance->name, $attributeInstance->in);
                Util::merge($newParameter, $attributeInstance);
            }
        }

        foreach ($this->reflectionPreparer->getArgumentTypes($reflectionMethod) as $types) {
            if (1 === count($types) && is_subclass_of($types[0]->getClassName(), HeaderRequestInterface::class)) {
                $reflectionClass = new ReflectionClass($types[0]->getClassName());

                $this->addHeaderParametersFromObject($operation, $reflectionClass);
            }
        }
    }

    private function addHeaderParametersFromObject(Operation $operation, ReflectionClass $reflectionClass): void
    {
        $propertyNames = $this->propertyInfoExtractor->getProperties($reflectionClass->getName());

        foreach ($propertyNames as $propertyName) {
            try {
                $reflectionProperty = $reflectionClass->getProperty($propertyName);
            } catch (ReflectionException) {
                continue;
            }

            if ($reflectionProperty->isStatic()) {
                continue;
            }

            if ($reflectionClass->getName() !== $reflectionProperty->getDeclaringClass()->getName()) {
                continue;
            }

            if (!$this->propertyInfoExtractor->isReadable($reflectionClass->getName(), $propertyName)) {
                continue;
            }

            $parameter = Util::getOperationParameter($operation, $propertyName, 'header');

            Util::merge($parameter, [
                'required' => $this->isRequired($reflectionProperty),
                'schema' => $this->getSchema($reflectionProperty),
            ], true);

            if ($this->isDeprecated($reflectionProperty)) {
                $parameter->deprecated = true;
            }
        }
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

        $types = $this->propertyInfoExtractor->getTypes(
            $reflectionProperty->getDeclaringClass()->getName(),
            $reflectionProperty->getName()
        );

        $this->propertyDescriber->describe($schema, ...$types);

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
