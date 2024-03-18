<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers;

use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriberInterface;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use Symfony\Component\PropertyInfo\Type;

final class NullableDescriber implements PropertyTypeDescriberInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        if (Generator::UNDEFINED === $property->nullable) {
            $property->nullable = true;
        }
    }

    public function supports(Type ...$types): bool
    {
        foreach ($types as $type) {
            if ($type->isNullable()) {
                return true;
            }
        }

        return false;
    }
}
