<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriberInterface;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use Symfony\Component\PropertyInfo\Type;

class CompoundPropertyDescriber implements PropertyTypeDescriberInterface
{
    public function __construct(
        private PropertyTypeDescriber $propertyDescriber
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        $hasNullable = $this->hasNullable(...$types);

        if ($hasNullable && 2 === count($types)) {
            foreach ($types as $type) {
                $this->propertyDescriber->describe($property, $context, $type);
            }

            return;
        }

        $property->oneOf = Generator::UNDEFINED !== $property->oneOf ? $property->oneOf : [];

        foreach ($types as $type) {
            /** @var Schema $schema */
            $schema = Util::createChild($property, Schema::class);

            $property->oneOf[] = $schema;

            $this->propertyDescriber->describe($schema, $context, $type);
        }
    }

    public function supports(Type ...$types): bool
    {
        return count($types) >= 2;
    }

    private function hasNullable(Type ...$types): bool
    {
        foreach ($types as $type) {
            if (Type::BUILTIN_TYPE_NULL === $type->getBuiltinType()) {
                return true;
            }
        }

        return false;
    }
}
