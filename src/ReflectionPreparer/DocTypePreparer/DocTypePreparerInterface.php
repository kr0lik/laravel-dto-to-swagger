<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer;

use phpDocumentor\Reflection\Type as DocType;
use Symfony\Component\PropertyInfo\Type;

interface DocTypePreparerInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return Type[]
     */
    public function prepare(DocType $docType, array $context = []): array;

    public function supports(DocType $docType): bool;
}
