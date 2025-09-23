<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriberInterface;
use OpenApi\Annotations\AdditionalProperties;
use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

class AssociativeDescriber implements PropertyTypeDescriberInterface
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
        $type = $types[0]->getCollectionValueTypes()[0] ?? null;

        if (null === $type) {
            return;
        }

        $property->type = 'object';
        /** @var AdditionalProperties $property */
        $property = Util::getChild($property, AdditionalProperties::class);
        $this->propertyDescriber->describe($property, $context, $type);
    }

    public function supports(Type ...$types): bool
    {
        if (count($types) !== 1 || !$types[0]->isCollection()) {
            return false;
        }

        $key = $types[0]->getCollectionKeyTypes()[0] ?? null;

        return $key?->getBuiltinType() === 'string';
    }
}
