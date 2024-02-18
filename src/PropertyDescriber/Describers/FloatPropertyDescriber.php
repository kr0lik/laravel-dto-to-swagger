<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber\Describers;

use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriberInterface;
use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

class FloatPropertyDescriber implements PropertyDescriberInterface
{
    public function describe(Schema $property, Type ...$types): void
    {
        $property->type = 'number';
        $property->format = 'float';
    }

    public function supports(Type ...$types): bool
    {
        return 1 === count($types) && Type::BUILTIN_TYPE_FLOAT === $types[0]->getBuiltinType();
    }
}
