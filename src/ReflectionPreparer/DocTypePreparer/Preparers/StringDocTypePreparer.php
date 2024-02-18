<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\DocTypePreparerInterface;
use phpDocumentor\Reflection\Type as DocType;
use phpDocumentor\Reflection\Types\String_;
use Symfony\Component\PropertyInfo\Type;

class StringDocTypePreparer implements DocTypePreparerInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     *
     * @return Type[]
     */
    public function prepare(DocType $docType, array $context = []): array
    {
        $propertyInfo = new Type(Type::BUILTIN_TYPE_STRING);

        return [$propertyInfo];
    }

    public function supports(DocType $docType): bool
    {
        return $docType instanceof String_;
    }
}
