<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriberInterface;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

class ArrayDescriber implements PropertyTypeDescriberInterface
{
    public function __construct(
        private PropertyTypeDescriber $propertyDescriber,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        $property->type = 'array';

        /** @var Items $property */
        $property = Util::getChild($property, Items::class);

        $type = $types[0]->getCollectionValueTypes()[0] ?? null;

        if ($type === null) {
            return;
        }

        $this->propertyDescriber->describe($property, $context, $type);
    }

    public function supports(Type ...$types): bool
    {
        return count($types) === 1 && $types[0]->isCollection();
    }
}
