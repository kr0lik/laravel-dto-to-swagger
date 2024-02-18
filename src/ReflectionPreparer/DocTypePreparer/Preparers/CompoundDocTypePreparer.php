<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\DocTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\DocTypePreparerInterface;
use phpDocumentor\Reflection\Type as DocType;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Null_;
use Symfony\Component\PropertyInfo\Type;

class CompoundDocTypePreparer implements DocTypePreparerInterface
{
    public function __construct(
        private DocTypePreparer $tagPreparer
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     *
     * @return Type[]
     */
    public function prepare(DocType $docType, array $context = []): array
    {
        /** @var Compound $docType */
        $propertyInfos = [];

        $hasNullable = false;

        foreach ($docType->getIterator() as $type) {
            if ($type instanceof Null_) {
                $hasNullable = true;

                continue;
            }

            $propertyInfos = array_merge($propertyInfos, $this->tagPreparer->prepare($type, $context));
        }

        if ($hasNullable) {
            $propertyInfos = array_map(
                static fn (Type $type): Type => new Type($type->getBuiltinType(), true, $type->getClassName(), $type->isCollection(), $type->getCollectionKeyTypes(), $type->getCollectionValueTypes()),
                $propertyInfos
            );
        }

        return $propertyInfos;
    }

    public function supports(DocType $docType): bool
    {
        return $docType instanceof Compound;
    }
}
