<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber;

use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

interface PropertyDescriberInterface
{
    public function describe(Schema $property, Type ...$types): void;

    public function supports(Type ...$types): bool;
}
