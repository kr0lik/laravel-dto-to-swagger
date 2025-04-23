<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers;

use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriberInterface;
use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

class BooleanDescriber implements PropertyTypeDescriberInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        $property->type = 'boolean';
    }

    public function supports(Type ...$types): bool
    {
        return count($types) === 1 && $types[0]->getBuiltinType() === Type::BUILTIN_TYPE_BOOL;
    }
}
