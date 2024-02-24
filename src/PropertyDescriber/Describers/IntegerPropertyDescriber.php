<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber\Describers;

use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriberInterface;
use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

class IntegerPropertyDescriber implements PropertyDescriberInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        $property->type = 'integer';
    }

    public function supports(Type ...$types): bool
    {
        return 1 === count($types) && Type::BUILTIN_TYPE_INT === $types[0]->getBuiltinType();
    }
}
