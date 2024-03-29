<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers;

use BackedEnum;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriberInterface;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparer;
use OpenApi\Annotations\Schema;
use ReflectionEnum;
use ReflectionEnumUnitCase;
use ReflectionException;
use Symfony\Component\PropertyInfo\Type;

class EnumDescriber implements PropertyTypeDescriberInterface
{
    public function __construct(
        private PropertyTypeDescriber $propertyDescriber,
        private RefTypePreparer $refTypePreparer,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws ReflectionException
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        $class = $types[0]->getClassName();

        if (null === $class) {
            return;
        }

        $enumReflection = new ReflectionEnum($class);

        $this->propertyDescriber->describe($property, $context, ...$this->refTypePreparer->prepare($enumReflection->getBackingType()));

        $property->enum = array_map(static fn (ReflectionEnumUnitCase $case): mixed => $case->getValue(), $enumReflection->getCases());
    }

    public function supports(Type ...$types): bool
    {
        return 1 === count($types)
            && Type::BUILTIN_TYPE_OBJECT === $types[0]->getBuiltinType()
            && is_a($types[0]->getClassName(), BackedEnum::class, true);
    }
}
