<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriber;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriberInterface;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

class ArrayPropertyDescriber implements PropertyDescriberInterface
{
    public function __construct(
        private PropertyDescriber $propertyDescriber,
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

        if (null === $type) {
            return;
        }

        $this->propertyDescriber->describe($property, $context, $type);
    }

    public function supports(Type ...$types): bool
    {
        return 1 === count($types) && $types[0]->isCollection();
    }
}
