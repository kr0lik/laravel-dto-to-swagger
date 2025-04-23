<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers;

use BackedEnum;
use DateTimeInterface;
use Kr0lik\DtoToSwagger\Helper\ContextHelper;
use Kr0lik\DtoToSwagger\Helper\NameHelper;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriberInterface;
use Kr0lik\DtoToSwagger\ReflectionPreparer\Helper\ClassHelper;
use Kr0lik\DtoToSwagger\ReflectionPreparer\PhpDocReader;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use Kr0lik\DtoToSwagger\Register\OpenApiRegister;
use Kr0lik\DtoToSwagger\Trait\IsRequiredTrait;
use OpenApi\Annotations\Schema;
use OpenApi\Attributes\Property;
use OpenApi\Generator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use stdClass;
use Symfony\Component\PropertyInfo\Type;

class ObjectDescriber implements PropertyTypeDescriberInterface
{
    use IsRequiredTrait;

    public const SKIP_TYPES_CONTEXT = 'object_type_describer_skip_types';

    public const SKIP_ATTRIBUTES_CONTEXT = 'object_type_describer_skip_attributes';

    public function __construct(
        private OpenApiRegister $openApiRegister,
        private PropertyTypeDescriber $propertyDescriber,
        private ReflectionPreparer $reflectionPreparer,
        private PhpDocReader $phpDocReader,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws ReflectionException
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        $class = $types[0]->getClassName();

        if ($class === null || $class === stdClass::class) {
            $property->type = 'object';

            return;
        }

        $path = $this->openApiRegister->findSchemaPath($class);

        if ($path !== null) {
            $property->ref = $path;

            return;
        }

        $reflectionClass = new ReflectionClass($class);

        $schema = $this->getSchema($reflectionClass, $context);

        if ($schema === null) {
            return;
        }

        $property->ref = $this->openApiRegister->registerSchema($schema, $class);
    }

    public function supports(Type ...$types): bool
    {
        return count($types) === 1
            && $types[0]->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT
            && $types[0]->getClassName() !== null
            && ! is_a($types[0]->getClassName(), DateTimeInterface::class, true)
            && ! is_a($types[0]->getClassName(), BackedEnum::class, true);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws ReflectionException
     */
    private function getSchema(ReflectionClass $reflectionClass, array $context): ?Schema
    {
        $schema = new Schema(['type' => 'object']);

        $this->fillProperties($schema, $reflectionClass, $context);

        $isEmptySchema = $schema->properties === Generator::UNDEFINED
            || $schema->properties === [];

        if ($isEmptySchema) {
            return null;
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws ReflectionException
     */
    private function fillProperties(Schema $schema, ReflectionClass $reflectionClass, array $context): void
    {
        foreach (ClassHelper::getVisiblePropertiesRecursively($reflectionClass) as $reflectionProperty) {
            if ($this->isShouldSkipType($reflectionProperty, $context)) {
                continue;
            }

            $propertySchema = $this->getPropertySchema($reflectionProperty, $context);

            if ($propertySchema === null) {
                continue;
            }

            $description = $this->phpDocReader->getDescription($reflectionProperty);

            if ($description !== '') {
                $propertySchema->description = $description;
            }

            if ($schema->properties === Generator::UNDEFINED) {
                $schema->properties = [];
            }

            $schema->properties[] = $propertySchema;

            if ($this->isRequired($reflectionProperty)) {
                if ($schema->required === Generator::UNDEFINED) {
                    $schema->required = [];
                }

                $schema->required[] = NameHelper::getName($reflectionProperty);
            }

            $defaultValue = $this->getDefaultValue($reflectionProperty);

            if ($defaultValue !== null) {
                if (is_object($defaultValue) && is_a($defaultValue, BackedEnum::class, true)) {
                    $defaultValue = $defaultValue->value;
                }

                if (is_scalar($defaultValue)) {
                    $propertySchema->default = $defaultValue;
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getPropertySchema(ReflectionProperty $reflectionProperty, array $context): ?Property
    {
        $propertySchema = new Property;

        foreach ($reflectionProperty->getAttributes() as $attribute) {
            if ($this->isShouldSkipByAttribute($attribute, $context)) {
                return null;
            }

            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Property) {
                $propertySchema = $attributeInstance;
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

    /**
     * @throws ReflectionException
     */
    private function getDefaultValue(ReflectionProperty $reflectionProperty): mixed
    {
        if ($reflectionProperty->hasDefaultValue()) {
            return $reflectionProperty->getDefaultValue();
        }

        foreach ($reflectionProperty->getDeclaringClass()->getConstructor()?->getParameters() ?? [] as $constructorParameter) {
            if ($constructorParameter->getName() === $reflectionProperty->getName() && $constructorParameter->isDefaultValueAvailable()) {
                return $constructorParameter->getDefaultValue();
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function isShouldSkipType(ReflectionProperty $reflectionProperty, array $context): bool
    {
        if (
            ! array_key_exists(self::SKIP_TYPES_CONTEXT, $context)
            || ! is_array($context[self::SKIP_TYPES_CONTEXT])
            || $context[self::SKIP_TYPES_CONTEXT] === []
        ) {
            return false;
        }

        $reflectionType = $reflectionProperty->getType();

        if (! $reflectionType instanceof ReflectionNamedType) {
            return false;
        }

        foreach ($context[self::SKIP_TYPES_CONTEXT] as $class) {
            if ($reflectionType === $class) {
                return true;
            }

            if (is_subclass_of($reflectionType->getName(), $class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function isShouldSkipByAttribute(ReflectionAttribute $reflectionAttribute, array $context): bool
    {
        if (
            ! array_key_exists(self::SKIP_ATTRIBUTES_CONTEXT, $context)
            || ! is_array($context[self::SKIP_ATTRIBUTES_CONTEXT])
            || $context[self::SKIP_ATTRIBUTES_CONTEXT] === []
        ) {
            return false;
        }

        $attributeInstance = $reflectionAttribute->newInstance();

        /** @var class-string $class */
        foreach ($context[self::SKIP_ATTRIBUTES_CONTEXT] as $class) {
            if (is_a($attributeInstance, $class)) {
                return true;
            }

            if (is_subclass_of($attributeInstance, $class)) {
                return true;
            }
        }

        return false;
    }
}
