<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber\Describers;

use BackedEnum;
use DateTimeInterface;
use Hoa\Zformat\Parameter;
use Kr0lik\DtoToSwagger\Helper\ContextHelper;
use Kr0lik\DtoToSwagger\Helper\NameHelper;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriber;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriberInterface;
use Kr0lik\DtoToSwagger\ReflectionPreparer\Helper\ClassHelper;
use Kr0lik\DtoToSwagger\ReflectionPreparer\PhpDocReader;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use Kr0lik\DtoToSwagger\Register\SchemaRegister;
use OpenApi\Annotations\Schema;
use OpenApi\Attributes\Property;
use OpenApi\Generator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use stdClass;
use Symfony\Component\PropertyInfo\Type;

class ObjectPropertyDescriber implements PropertyDescriberInterface
{
    public function __construct(
        private PropertyDescriber $propertyDescriber,
        private SchemaRegister $schemaRegister,
        private ReflectionPreparer $reflectionPreparer,
        private PhpDocReader $phpDocReader,
        private ?string $fileUploadType,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws ReflectionException
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        $class = $types[0]->getClassName();

        if (null === $class || stdClass::class === $class) {
            $property->type = 'object';

            return;
        }

        $path = $this->schemaRegister->findPath($class);

        if (null !== $path) {
            $property->ref = $path;

            return;
        }

        $reflectionClass = new ReflectionClass($class);

        $schema = $this->getSchema($reflectionClass);

        if (null === $schema) {
            return;
        }

        $property->ref = $this->schemaRegister->register($schema, $class);
    }

    public function supports(Type ...$types): bool
    {
        return 1 === count($types)
            && Type::BUILTIN_TYPE_OBJECT === $types[0]->getBuiltinType()
            && !is_a($types[0]->getClassName(), DateTimeInterface::class, true)
            && !is_a($types[0]->getClassName(), BackedEnum::class, true);
    }

    private function getSchema(ReflectionClass $reflectionClass): ?Schema
    {
        $schema = new Schema(['type' => 'object']);

        $this->fillProperties($schema, $reflectionClass);

        if (
            Generator::UNDEFINED === $schema->properties
            || [] === $schema->properties
        ) {
            return null;
        }

        return $schema;
    }

    private function fillProperties(Schema $schema, ReflectionClass $reflectionClass): void
    {
        foreach (ClassHelper::getVisibleProperties($reflectionClass) as $reflectionProperty) {
            if (
                null !== $this->fileUploadType && '' !== $this->fileUploadType
                && $reflectionProperty->getType() instanceof ReflectionNamedType
                && (
                    $reflectionProperty->getType()->getName() === $this->fileUploadType
                    || is_subclass_of($reflectionProperty->getType()->getName(), $this->fileUploadType)
                )
            ) {
                continue;
            }

            $propertySchema = $this->getPropertySchema($reflectionProperty);

            if (null === $propertySchema) {
                continue;
            }

            $description = $this->phpDocReader->getDescription($reflectionProperty);

            if ('' !== $description) {
                $propertySchema->description = $description;
            }

            if (Generator::UNDEFINED === $schema->properties) {
                $schema->properties = [];
            }

            $schema->properties[] = $propertySchema;

            if ($this->isRequired($reflectionProperty)) {
                if (Generator::UNDEFINED === $schema->required) {
                    $schema->required = [];
                }

                $schema->required[] = NameHelper::getName($reflectionProperty);
            }
        }
    }

    private function getPropertySchema(ReflectionProperty $reflectionProperty): ?Property
    {
        $propertySchema = new Property();

        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Property) {
                $propertySchema = $attributeInstance;
            }

            if ($attributeInstance instanceof Parameter) {
                return null;
            }
        }

        $propertySchema->property = NameHelper::getName($reflectionProperty);

        if ($this->phpDocReader->isDeprecated($reflectionProperty)) {
            $propertySchema->deprecated = true;
        }

        $types = $this->reflectionPreparer->getTypes($reflectionProperty);

        $context = ContextHelper::getContext($reflectionProperty);

        $this->propertyDescriber->describe($propertySchema, $context, ...$types);

        return $propertySchema;
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
