<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber\Describers;

use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriberInterface;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use Symfony\Component\PropertyInfo\Type;

final class NullablePropertyDescriber implements PropertyDescriberInterface
{
    public function describe(Schema $property, Type ...$types): void
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
