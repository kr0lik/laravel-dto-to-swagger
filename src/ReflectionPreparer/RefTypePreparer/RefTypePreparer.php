<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer;

use ReflectionType;
use Symfony\Component\PropertyInfo\Type;

class RefTypePreparer
{
    /** @var RefTypePreparerInterface[] */
    private array $reflectionTypePreparers;

    /**
     * @param array<string, mixed> $context
     *
     * @return Type[]
     */
    public function prepare(ReflectionType $reflectionType, array $context = []): array
    {
        foreach ($this->reflectionTypePreparers as $reflectionTypePreparer) {
            if ($reflectionTypePreparer->supports($reflectionType)) {
                return $reflectionTypePreparer->prepare($reflectionType, $context);
            }
        }

        return [];
    }

    public function addPreparer(RefTypePreparerInterface $reflectionTypePreparer): void
    {
        $this->reflectionTypePreparers[] = $reflectionTypePreparer;
    }
}
