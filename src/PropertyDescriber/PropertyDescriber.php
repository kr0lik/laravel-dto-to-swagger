<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber;

use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

final class PropertyDescriber
{
    /**
     * @var PropertyDescriberInterface[]
     */
    private array $propertyDescribers;

    public function addPropertyDescriber(PropertyDescriberInterface $propertyDescriber): void
    {
        $this->propertyDescribers[] = $propertyDescriber;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        foreach ($this->getPropertyDescriber(...$types) as $propertyDescriber) {
            $propertyDescriber->describe($property, $context, ...$types);
        }
    }

    /**
     * @return iterable<PropertyDescriberInterface>
     */
    private function getPropertyDescriber(Type ...$types): iterable
    {
        foreach ($this->propertyDescribers as $propertyDescriber) {
            if ($propertyDescriber->supports(...$types)) {
                yield $propertyDescriber;
            }
        }
    }
}
