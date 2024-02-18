<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber\Describers;

use BackedEnum;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriber;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriberInterface;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparer;
use OpenApi\Annotations\Schema;
use ReflectionEnum;
use ReflectionEnumUnitCase;
use ReflectionException;
use Symfony\Component\PropertyInfo\Type;

class EnumPropertyDescriber implements PropertyDescriberInterface
{
    public function __construct(
        private PropertyDescriber $propertyDescriber,
        private RefTypePreparer $refTypePreparer,
    ) {}

    /**
     * @throws ReflectionException
     */
    public function describe(Schema $property, Type ...$types): void
    {
        $class = $types[0]->getClassName();

        if (null === $class) {
            return;
        }

        $enumReflection = new ReflectionEnum($class);

        $this->propertyDescriber->describe($property, ...$this->refTypePreparer->prepare($enumReflection->getBackingType()));

        $property->enum = array_map(static fn (ReflectionEnumUnitCase $case): mixed => $case->getValue(), $enumReflection->getCases());
    }

    public function supports(Type ...$types): bool
    {
        return 1 === count($types)
            && Type::BUILTIN_TYPE_OBJECT === $types[0]->getBuiltinType()
            && is_a($types[0]->getClassName(), BackedEnum::class, true);
    }
}
