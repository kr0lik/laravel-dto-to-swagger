<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriber;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriberInterface;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use Symfony\Component\PropertyInfo\Type;

class CompoundPropertyDescriber implements PropertyDescriberInterface
{
    public function __construct(
        private PropertyDescriber $propertyDescriber
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function describe(Schema $property, Type ...$types): void
    {
        $property->oneOf = Generator::UNDEFINED !== $property->oneOf ? $property->oneOf : [];

        foreach ($types as $type) {
            /** @var Schema $schema */
            $schema = Util::createChild($property, Schema::class, []);
            $property->oneOf[] = $schema;

            $this->propertyDescriber->describe($schema, $type);
        }
    }

    public function supports(Type ...$types): bool
    {
        return count($types) >= 2;
    }
}
